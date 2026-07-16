import 'dart:convert';
import 'dart:io';

import 'package:angel_granites_app/services/inventory_service.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';

Map<String, dynamic> _inventoryJson({
  required String code,
  required String description,
  String size = '4-0 X 0-8 X 2-4',
  String type = 'Tablet',
  String color = 'India Red',
  String location = 'Elberton',
}) {
  return {
    'code': code,
    'description': description,
    'size': size,
    'type': type,
    'color': color,
    'location': location,
    'quantity': 1,
  };
}

Future<void> _writeSavedInventory(
  Directory directory,
  List<Map<String, dynamic>> items,
) async {
  final file = File('${directory.path}/cached_inventory.json');
  await file.writeAsString(jsonEncode(items));
}

void main() {
  late Directory documentsDirectory;

  setUp(() async {
    documentsDirectory = await Directory.systemTemp.createTemp(
      'angel-inventory-cache-test-',
    );
  });

  tearDown(() async {
    if (await documentsDirectory.exists()) {
      await documentsDirectory.delete(recursive: true);
    }
  });

  test('cold-start search hydrates saved inventory without an API request',
      () async {
    await _writeSavedInventory(documentsDirectory, [
      _inventoryJson(
        code: 'CACHED-SIZE',
        description: 'Cached serpentine tablet',
      ),
    ]);

    var apiRequests = 0;
    final service = InventoryService(
      documentsDirectoryProvider: () async => documentsDirectory,
      httpClient: MockClient((request) async {
        apiRequests++;
        return http.Response('Unexpected request', 500);
      }),
    );

    final firstSearch = await service.fetchInventory(
      searchQuery: '4-0 x 2-4 x 0-8',
    );
    final secondSearch = await service.fetchInventory(
      searchQuery: 'cached serpentine',
    );

    expect(firstSearch.single.code, 'CACHED-SIZE');
    expect(secondSearch.single.code, 'CACHED-SIZE');
    expect(apiRequests, 0);
  });

  test('concurrent startup initialization performs one server refresh',
      () async {
    await _writeSavedInventory(documentsDirectory, [
      _inventoryJson(
        code: 'STALE-CACHE',
        description: 'Previously saved tablet',
      ),
    ]);

    var apiRequests = 0;
    final service = InventoryService(
      documentsDirectoryProvider: () async => documentsDirectory,
      httpClient: MockClient((request) async {
        apiRequests++;
        await Future<void>.delayed(const Duration(milliseconds: 25));
        return http.Response(
          jsonEncode({
            'success': true,
            'data': [
              {
                'Qty': 2,
                'Locationname': 'Barre',
                'EndProductCode': 'FRESH-SERVER',
                'EndProductDescription': 'Fresh server tablet',
                'Ptype': 'Tablet',
                'PColor': 'Premium Jet Black',
                'Size': '3-0 X 0-8 X 2-0',
              },
            ],
          }),
          200,
        );
      }),
    );

    await Future.wait([
      service.initialize(),
      service.initialize(),
      service.initialize(),
    ]);

    expect(apiRequests, 1);

    final searchResults = await service.fetchInventory(
      searchQuery: 'fresh server',
    );
    expect(searchResults.single.code, 'FRESH-SERVER');
    expect(apiRequests, 1);

    final savedJson = jsonDecode(
      await File('${documentsDirectory.path}/cached_inventory.json')
          .readAsString(),
    ) as List<dynamic>;
    expect(savedJson.single['code'], 'FRESH-SERVER');
  });

  test('successful server refresh invalidates derived search results',
      () async {
    var apiRequests = 0;
    final service = InventoryService(
      documentsDirectoryProvider: () async => documentsDirectory,
      httpClient: MockClient((request) async {
        apiRequests++;
        final code = apiRequests == 1 ? 'OLD-SNAPSHOT' : 'NEW-SNAPSHOT';
        return http.Response(
          jsonEncode({
            'success': true,
            'data': [
              {
                'Qty': 1,
                'Locationname': 'Elberton',
                'EndProductCode': code,
                'EndProductDescription': 'Target serpentine tablet',
                'Ptype': 'Tablet',
                'PColor': 'India Red',
                'Size': '4-0 X 0-8 X 2-4',
              },
            ],
          }),
          200,
        );
      }),
    );

    await service.fetchInventory(forceRefresh: true);
    final firstSearch = await service.fetchInventory(
      searchQuery: 'target serpentine',
    );
    expect(firstSearch.single.code, 'OLD-SNAPSHOT');

    // The service briefly coalesces callers from the same event cycle.
    await Future<void>.delayed(const Duration(milliseconds: 120));
    await service.fetchInventory(forceRefresh: true);

    final refreshedSearch = await service.fetchInventory(
      searchQuery: 'target serpentine',
    );
    expect(refreshedSearch.single.code, 'NEW-SNAPSHOT');
    expect(apiRequests, 2);
  });

  test('failed later page does not replace a complete cached inventory',
      () async {
    await _writeSavedInventory(documentsDirectory, [
      _inventoryJson(
        code: 'COMPLETE-CACHE',
        description: 'Saved complete inventory tablet',
      ),
    ]);

    var apiRequests = 0;
    final service = InventoryService(
      documentsDirectoryProvider: () async => documentsDirectory,
      httpClient: MockClient((request) async {
        apiRequests++;
        if (apiRequests == 1) {
          return http.Response(
            jsonEncode({
              'success': true,
              'data': List.generate(
                1000,
                (index) => {
                  'Qty': 1,
                  'Locationname': 'Barre',
                  'EndProductCode': 'PARTIAL-$index',
                  'EndProductDescription': 'Partial page tablet $index',
                  'Ptype': 'Tablet',
                  'PColor': 'Gray',
                  'Size': '3-0 X 0-8 X 2-0',
                },
              ),
            }),
            200,
          );
        }
        return http.Response('Temporary upstream error', 503);
      }),
    );

    final hydrated = await service.fetchInventory();
    expect(hydrated.single.code, 'COMPLETE-CACHE');
    expect(apiRequests, 0);

    await expectLater(
      service.fetchInventory(forceRefresh: true),
      throwsA(isA<Exception>()),
    );

    final afterFailure = await service.fetchInventory(
      searchQuery: 'saved complete',
    );
    expect(afterFailure.single.code, 'COMPLETE-CACHE');
    expect(apiRequests, 2);
  });
}
