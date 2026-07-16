import '../models/inventory_item.dart';

/// Field-aware inventory search and relevance ranking.
///
/// Inventory dimensions are commonly typed with different separators and
/// casing (for example `4-0 x 2-4 x 0-8` versus `4-0 X 2-4 X 0-8`). Search
/// therefore compares both a readable normalized value and a compact value.
class InventorySearch {
  const InventorySearch._();

  static const Set<String> _spokenSearchFillers = {
    'a',
    'all',
    'any',
    'are',
    'available',
    'availability',
    'can',
    'could',
    'do',
    'does',
    'find',
    'for',
    'full',
    'have',
    'in',
    'inventory',
    'is',
    'me',
    'of',
    'please',
    'search',
    'show',
    'stock',
    'stone',
    'stones',
    'the',
    'there',
    'thick',
    'thickness',
    'with',
    'you',
  };

  static String normalizeQuery(String value) {
    return value
        .toLowerCase()
        .replaceAll('\u00d7', 'x')
        .replaceAll(RegExp(r'[^a-z0-9]+'), ' ')
        .replaceAllMapped(
          RegExp(r'(\d)x(\d)'),
          (match) => '${match.group(1)} x ${match.group(2)}',
        )
        .trim()
        .replaceAll(RegExp(r'\s+'), ' ');
  }

  /// Removes conversational words Siri commonly includes around the useful
  /// inventory terms. For example, "is AG-298 available" becomes "ag 298"
  /// and "any 0-8 thickness stone" becomes "0 8".
  static String searchTerms(String value) {
    final normalized = normalizeQuery(
      value.replaceAll(
        RegExp(r'\bhead\s+stones?\b', caseSensitive: false),
        'headstone',
      ),
    );
    if (normalized.isEmpty) return normalized;

    final terms = normalized
        .split(' ')
        .where((term) => term == 'x' || !_spokenSearchFillers.contains(term))
        .toList();

    // "Headstone" is a broad spoken product noun. Keep the descriptive part
    // in queries such as "heart headstone"; map a headstone-only request to
    // the inventory's corresponding Tablet product type.
    final hadHeadstone = terms.remove('headstone');
    if (hadHeadstone && terms.where((term) => term != 'x').isEmpty) {
      terms.add('tablet');
    }

    return terms.join(' ').trim();
  }

  static List<InventoryItem> filterAndRank(
    Iterable<InventoryItem> items, {
    String? query,
    String? type,
    String? color,
    String? location,
  }) {
    final normalizedQuery = searchTerms(query ?? '');
    final normalizedType = normalizeQuery(type ?? '');
    final normalizedColor = normalizeQuery(color ?? '');
    final normalizedLocation = normalizeQuery(location ?? '');

    final matches = <_RankedInventoryItem>[];

    for (final item in items) {
      if (!_matchesType(item, normalizedType) ||
          !_matchesColor(item, normalizedColor) ||
          !_matchesLocation(item, normalizedLocation)) {
        continue;
      }

      final score = _score(item, normalizedQuery);
      if (score != null) {
        matches.add(_RankedInventoryItem(item, score));
      }
    }

    matches.sort((a, b) {
      final byScore = a.score.compareTo(b.score);
      if (byScore != 0) return byScore;

      // Prefer an available item when two records are equally relevant.
      final byQuantity = b.item.quantity.compareTo(a.item.quantity);
      if (byQuantity != 0) return byQuantity;

      final byDescription = a.item.description
          .toLowerCase()
          .compareTo(b.item.description.toLowerCase());
      if (byDescription != 0) return byDescription;

      return a.item.code.toLowerCase().compareTo(b.item.code.toLowerCase());
    });

    return matches.map((match) => match.item).toList();
  }

  static bool _matchesType(InventoryItem item, String type) {
    if (type.isEmpty) return true;
    return normalizeQuery(item.type) == type;
  }

  static bool _matchesColor(InventoryItem item, String color) {
    if (color.isEmpty) return true;
    return normalizeQuery(item.color) == color;
  }

  static bool _matchesLocation(InventoryItem item, String location) {
    if (location.isEmpty) return true;
    return normalizeQuery(item.location) == location;
  }

  static int? _score(InventoryItem item, String query) {
    if (query.isEmpty) return 1000;

    final compactQuery = _compact(query);
    final queryDimensions = _dimensionSignature(query);
    final sizeDimensions = _dimensionSignature(normalizeQuery(item.size));

    // Monument dimensions are often entered as length x height x thickness,
    // while inventory stores length x thickness x height. Treat those as the
    // same exact size before considering weaker text matches.
    if (queryDimensions != null && queryDimensions == sizeDimensions) {
      return _compact(normalizeQuery(item.size)) == compactQuery ? 0 : 5;
    }

    // A spoken thickness request such as "0-8 thickness" is reduced to the
    // component "0 8". Prefer sizes containing that complete component over
    // incidental mentions elsewhere in the record.
    if (_isDimensionComponent(query) &&
        _sizeComponents(item.size).contains(compactQuery)) {
      return 8;
    }

    // Search runs after every keystroke, so a query such as `3-0x2` is a
    // partially entered size rather than four unrelated numeric tokens. Keep
    // matching the completed `3-0` component and treat `2` as the prefix of
    // the next component. Once it becomes `3-0x2-4`, require both complete
    // components to exist in the item's size. Dimension-like queries must not
    // fall through to the broad token matcher, where values such as `2025`
    // and `0-0.39` can create unrelated sample matches.
    final partialDimensions = _partialDimensionQuery(query);
    if (queryDimensions == null && partialDimensions != null) {
      if (_matchesPartialDimensions(item.size, partialDimensions)) {
        return partialDimensions.every((component) => component.isComplete)
            ? 10
            : 15;
      }
      return null;
    }

    final fields = <_SearchField>[
      _SearchField(item.size, 0),
      _SearchField(item.code, 10),
      _SearchField(item.design, 20),
      _SearchField(item.color, 30),
      _SearchField(item.type, 40),
      _SearchField(item.finish, 50),
      _SearchField(item.description, 60),
      _SearchField(item.location, 70),
    ];

    int? bestScore;
    for (final field in fields) {
      final value = normalizeQuery(field.value);
      if (value.isEmpty) continue;

      final compactValue = _compact(value);
      int? fieldScore;

      if (value == query || compactValue == compactQuery) {
        fieldScore = field.priority;
      } else if (value.startsWith(query) ||
          compactValue.startsWith(compactQuery)) {
        fieldScore = 100 + field.priority;
      } else if (value.contains(query) || compactValue.contains(compactQuery)) {
        fieldScore = 200 + field.priority;
      }

      if (fieldScore != null && (bestScore == null || fieldScore < bestScore)) {
        bestScore = fieldScore;
      }
    }

    if (bestScore != null) return bestScore;

    final tokens = query
        .split(' ')
        .where((token) => token.isNotEmpty && token != 'x')
        .toSet();
    final searchableText = fields
        .map((field) => normalizeQuery(field.value))
        .where((value) => value.isNotEmpty)
        .join(' ');
    final searchableTokens = searchableText.split(' ').toSet();

    if (tokens.isNotEmpty &&
        tokens.every((token) =>
            searchableTokens.any((candidate) => candidate.startsWith(token)))) {
      return 400;
    }

    return null;
  }

  static String _compact(String value) =>
      value.replaceAll(RegExp(r'[^a-z0-9]'), '');

  static String? _dimensionSignature(String value) {
    final parts = value.split('x').map((part) => part.trim()).toList();
    if (parts.length != 3 ||
        parts.any((part) => !RegExp(r'\d').hasMatch(part))) {
      return null;
    }

    final dimensions = parts.map(_compact).toList()..sort();
    return dimensions.join('|');
  }

  static bool _isDimensionComponent(String value) {
    return RegExp(r'^\d+\s+\d+$').hasMatch(value);
  }

  static List<_DimensionQueryComponent>? _partialDimensionQuery(
    String value,
  ) {
    if (!value.contains('x')) return null;

    final parts = value.split('x');
    if (parts.length > 3) return null;

    final components = <_DimensionQueryComponent>[];
    for (var index = 0; index < parts.length; index++) {
      final part = parts[index].trim();
      if (part.isEmpty) {
        // A trailing x is valid while the customer is entering the next
        // dimension. An empty component in the middle is not.
        if (index == parts.length - 1) continue;
        return null;
      }

      final match = RegExp(r'^(\d+)(?:\s+(\d+))?$').firstMatch(part);
      if (match == null) return null;

      components.add(
        _DimensionQueryComponent(
          _compact(part),
          isComplete: match.group(2) != null,
        ),
      );
    }

    return components.isEmpty ? null : components;
  }

  static bool _matchesPartialDimensions(
    String size,
    List<_DimensionQueryComponent> queryComponents,
  ) {
    final remainingSizeComponents = _sizeComponents(size).toList();

    for (final queryComponent in queryComponents) {
      final matchingIndex = remainingSizeComponents.indexWhere(
        (sizeComponent) => queryComponent.isComplete
            ? sizeComponent == queryComponent.value
            : sizeComponent.startsWith(queryComponent.value),
      );
      if (matchingIndex == -1) return false;
      remainingSizeComponents.removeAt(matchingIndex);
    }

    return true;
  }

  static Set<String> _sizeComponents(String value) {
    return normalizeQuery(value)
        .split('x')
        .map((component) => _compact(component.trim()))
        .where((component) => component.isNotEmpty)
        .toSet();
  }
}

class _SearchField {
  final String value;
  final int priority;

  const _SearchField(this.value, this.priority);
}

class _RankedInventoryItem {
  final InventoryItem item;
  final int score;

  const _RankedInventoryItem(this.item, this.score);
}

class _DimensionQueryComponent {
  final String value;
  final bool isComplete;

  const _DimensionQueryComponent(this.value, {required this.isComplete});
}
