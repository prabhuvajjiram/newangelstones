import Foundation
import AppIntents

// Shared Apple-platform source: this file belongs to both the iOS and macOS
// Runner targets. The stable App Shortcut works on current operating systems.
// The preliminary 27-only system search schema remains separately gated until
// the project is built with Xcode 27; see docs/apple-27-siri-inventory.md.

enum PendingInventorySearch {
  static let userDefaultsKey = "pendingSiriInventorySearch"

  static func save(_ query: String) {
    let trimmedQuery = query.trimmingCharacters(in: .whitespacesAndNewlines)
    guard !trimmedQuery.isEmpty else { return }
    UserDefaults.standard.set(trimmedQuery, forKey: userDefaultsKey)
  }

  static func take() -> String? {
    guard let query = UserDefaults.standard.string(forKey: userDefaultsKey) else {
      return nil
    }
    UserDefaults.standard.removeObject(forKey: userDefaultsKey)
    return query
  }
}

/// Represents the live answer to a natural-language stock question. Modeling
/// the answer as an App Entity lets Siri resolve arbitrary server-backed terms
/// in an App Shortcut phrase instead of requiring a second prompt.
@available(iOS 16.0, macOS 13.0, *)
struct InventoryAvailabilityEntity: AppEntity {
  static let typeDisplayRepresentation = TypeDisplayRepresentation(
    name: "Inventory Availability"
  )
  static let defaultQuery = InventoryAvailabilityQuery()

  var id: String

  @Property(title: "Inventory search")
  var searchText: String

  @Property(title: "Live inventory result")
  var resultSummary: String

  var displayRepresentation: DisplayRepresentation {
    DisplayRepresentation(
      title: "\(searchText)",
      subtitle: "\(resultSummary)",
      image: .init(systemName: "shippingbox")
    )
  }

  init(searchText: String, resultSummary: String) {
    let trimmedSearch = searchText.trimmingCharacters(in: .whitespacesAndNewlines)
    id = trimmedSearch.lowercased()
    self.searchText = trimmedSearch
    self.resultSummary = resultSummary
  }
}

@available(iOS 16.0, macOS 13.0, *)
struct InventoryAvailabilityQuery: EntityStringQuery {
  func entities(for identifiers: [String]) async throws -> [InventoryAvailabilityEntity] {
    var entities: [InventoryAvailabilityEntity] = []
    for identifier in identifiers {
      entities.append(await InventoryAvailabilityClient.shared.entity(for: identifier))
    }
    return entities
  }

  func entities(matching string: String) async throws -> [InventoryAvailabilityEntity] {
    let trimmedSearch = string.trimmingCharacters(in: .whitespacesAndNewlines)
    guard !trimmedSearch.isEmpty else { return [] }
    return [await InventoryAvailabilityClient.shared.entity(for: trimmedSearch)]
  }
}

#if APPLE27_SIRI_ENABLED

/// The 27 releases' system in-app search schema lets Siri and Apple
/// Intelligence pass a natural-language inventory request into the existing
/// Angel Granites search screen on iPhone or Mac.
@available(iOS 27.0, macOS 27.0, *)
@AppIntent(schema: .system.searchInApp)
struct SearchAngelGranitesInventoryIntent: ShowInAppSearchResultsIntent {
  static var searchScopes: [StringSearchScope] = [.general]
  static var allowedExecutionTargets: IntentExecutionTargets = .main

  var criteria: StringSearchCriteria

  @MainActor
  func perform() async throws -> some IntentResult {
    PendingInventorySearch.save(criteria.term)
    return .result()
  }
}
#endif

/// A stable App Shortcut that answers a stock question without opening the
/// Flutter interface. This remains useful on pre-27 systems and provides the
/// direct spoken response while the 27 schema handles "show me" searches.
@available(iOS 16.0, macOS 13.0, *)
struct CheckInventoryAvailabilityIntent: AppIntent {
  static var title: LocalizedStringResource = "Check Inventory Availability"
  static var description = IntentDescription(
    "Checks live Angel Granites stock by design, color, product type, or dimensions."
  )

  @Parameter(
    title: "Stone or monument",
    description: "For example AG-298, heart headstone, 0-8 thickness, or 4-0 x 0-8 x 2-4."
  )
  var availability: InventoryAvailabilityEntity

  static var parameterSummary: some ParameterSummary {
    Summary("Check live inventory for \(\.$availability)")
  }

  func perform() async -> some IntentResult & ReturnsValue<String> & ProvidesDialog {
    let summary = availability.resultSummary
    return .result(value: summary, dialog: "\(summary)")
  }
}

@available(iOS 16.0, macOS 13.0, *)
struct AngelGranitesShortcuts: AppShortcutsProvider {
  static var shortcutTileColor: ShortcutTileColor = .yellow

  static var appShortcuts: [AppShortcut] {
    AppShortcut(
      intent: CheckInventoryAvailabilityIntent(),
      phrases: [
        "Check \(\.$availability) in \(.applicationName)",
        "Is \(\.$availability) available in \(.applicationName)",
        "Search \(.applicationName) for \(\.$availability)",
        "Check live inventory in \(.applicationName)",
        "Ask \(.applicationName) about inventory",
      ],
      shortTitle: "Check Live Inventory",
      systemImageName: "shippingbox"
    )
  }
}

private actor InventoryAvailabilityClient {
  static let shared = InventoryAvailabilityClient()

  private struct ProxyResponse: Decodable {
    let data: [Record]
    let pagination: Pagination?
  }

  private struct Pagination: Decodable {
    let hasNextPage: Bool
  }

  private struct Record: Decodable {
    let quantity: Int
    let code: String
    let description: String
    let color: String
    let size: String
    let location: String
    let type: String
    let design: String
    let finish: String

    enum CodingKeys: String, CodingKey {
      case quantity = "Qty"
      case code = "EndProductCode"
      case description = "EndProductDescription"
      case color = "PColor"
      case size = "Size"
      case location = "Locationname"
      case type = "Ptype"
      case design = "PDesign"
      case finish = "PFinish"
    }
  }

  private struct RankedRecord {
    let record: Record
    let score: Int
  }

  private var cachedRecords: [Record] = []
  private var cacheDate: Date?

  @available(iOS 16.0, macOS 13.0, *)
  func entity(for rawQuery: String) async -> InventoryAvailabilityEntity {
    let trimmedQuery = rawQuery.trimmingCharacters(in: .whitespacesAndNewlines)
    let summary: String
    do {
      summary = try await self.summary(for: trimmedQuery)
    } catch {
      summary = "I couldn't reach live Angel Granites inventory right now. Please try again shortly."
    }
    return InventoryAvailabilityEntity(
      searchText: trimmedQuery,
      resultSummary: summary
    )
  }

  func summary(for rawQuery: String) async throws -> String {
    let query = Self.searchTerms(rawQuery)
    guard !query.isEmpty else {
      return "Tell me a design, stone color, product type, or monument size to check."
    }

    let records = try await inventory()
    let matches = records.compactMap { record -> RankedRecord? in
      guard let score = Self.score(record, for: query) else { return nil }
      return RankedRecord(record: record, score: score)
    }.sorted {
      if $0.score != $1.score { return $0.score < $1.score }
      if $0.record.quantity != $1.record.quantity {
        return $0.record.quantity > $1.record.quantity
      }
      return $0.record.description < $1.record.description
    }

    guard let best = matches.first else {
      return "I couldn't find live stock matching \(rawQuery). Try a design code, color, type, or full size."
    }

    let availableMatches = matches.filter { $0.record.quantity > 0 }
    let totalQuantity = availableMatches.reduce(0) { $0 + $1.record.quantity }
    let item = best.record
    let label = item.design.isEmpty ? item.description : item.design
    let details = [item.color, item.size, item.location]
      .filter { !$0.isEmpty }
      .joined(separator: ", ")

    if totalQuantity == 0 {
      return "I found \(matches.count) matching stock records for \(rawQuery), but none currently have available quantity."
    }

    return "Yes. I found \(availableMatches.count) matching stock records totaling \(totalQuantity) pieces. The best match is \(label), \(details), with \(item.quantity) available."
  }

  private func inventory() async throws -> [Record] {
    if let cacheDate,
       Date().timeIntervalSince(cacheDate) < 2 * 60 * 60,
       !cachedRecords.isEmpty {
      return cachedRecords
    }

    var records: [Record] = []
    var page = 1
    var hasNextPage = true

    while hasNextPage && page <= 10 {
      var components = URLComponents(
        string: "https://theangelstones.com/inventory-proxy.php"
      )!
      components.queryItems = [
        URLQueryItem(name: "hasdesc", value: "false"),
        URLQueryItem(name: "description", value: ""),
        URLQueryItem(name: "ptype", value: ""),
        URLQueryItem(name: "pcolor", value: ""),
        URLQueryItem(name: "pdesign", value: ""),
        URLQueryItem(name: "pfinish", value: ""),
        URLQueryItem(name: "psize", value: ""),
        URLQueryItem(name: "locid", value: ""),
        URLQueryItem(name: "page", value: String(page)),
        URLQueryItem(name: "pageSize", value: "1000"),
      ]

      var request = URLRequest(url: components.url!)
      request.timeoutInterval = 30
      request.setValue("AngelGranites-Mobile-App/1.0", forHTTPHeaderField: "User-Agent")
      request.setValue("application/json", forHTTPHeaderField: "Accept")

      let (data, response) = try await URLSession.shared.data(for: request)
      guard let httpResponse = response as? HTTPURLResponse,
            (200..<300).contains(httpResponse.statusCode) else {
        throw URLError(.badServerResponse)
      }

      let result = try JSONDecoder().decode(ProxyResponse.self, from: data)
      records.append(contentsOf: result.data)
      hasNextPage = result.pagination?.hasNextPage ?? false
      page += 1
    }

    cachedRecords = records
    cacheDate = Date()
    return records
  }

  private static let fillerWords: Set<String> = [
    "a", "all", "any", "are", "available", "availability", "can", "could",
    "do", "does", "find", "for", "full", "have", "in", "inventory", "is",
    "me", "of", "please", "search", "show", "stock", "stone", "stones",
    "the", "there", "thick", "thickness", "with", "you",
  ]

  private static func normalize(_ value: String) -> String {
    value.lowercased()
      .replacingOccurrences(of: "×", with: "x")
      .replacingOccurrences(
        of: "[^a-z0-9]+",
        with: " ",
        options: .regularExpression
      )
      .split(whereSeparator: \.isWhitespace)
      .joined(separator: " ")
  }

  private static func searchTerms(_ value: String) -> String {
    let headstoneNormalized = value.replacingOccurrences(
      of: "\\bhead\\s+stones?\\b",
      with: "headstone",
      options: [.regularExpression, .caseInsensitive]
    )
    var terms = normalize(headstoneNormalized)
      .split(separator: " ")
      .map(String.init)
      .filter { $0 == "x" || !fillerWords.contains($0) }

    if let index = terms.firstIndex(of: "headstone") {
      terms.remove(at: index)
      if terms.allSatisfy({ $0 == "x" }) { terms.append("tablet") }
    }
    return terms.joined(separator: " ")
  }

  private static func compact(_ value: String) -> String {
    normalize(value).replacingOccurrences(of: " ", with: "")
  }

  private static func dimensionSignature(_ value: String) -> [String]? {
    let parts = normalize(value).split(separator: "x").map {
      $0.replacingOccurrences(of: " ", with: "")
    }
    guard parts.count == 3, parts.allSatisfy({ !$0.isEmpty }) else { return nil }
    return parts.sorted()
  }

  private static func score(_ record: Record, for query: String) -> Int? {
    let compactQuery = compact(query)
    if let queryDimensions = dimensionSignature(query),
       queryDimensions == dimensionSignature(record.size) {
      return 0
    }

    let fields: [(String, Int)] = [
      (record.size, 0), (record.code, 10), (record.design, 20),
      (record.color, 30), (record.type, 40), (record.finish, 50),
      (record.description, 60), (record.location, 70),
    ]
    var best: Int?

    for (value, priority) in fields where !value.isEmpty {
      let normalizedValue = normalize(value)
      let compactValue = compact(value)
      let candidate: Int?
      if normalizedValue == query || compactValue == compactQuery {
        candidate = priority
      } else if normalizedValue.hasPrefix(query) || compactValue.hasPrefix(compactQuery) {
        candidate = 100 + priority
      } else if normalizedValue.contains(query) || compactValue.contains(compactQuery) {
        candidate = 200 + priority
      } else {
        candidate = nil
      }
      if let candidate, best == nil || candidate < best! { best = candidate }
    }
    if let best { return best }

    let tokens = Set(query.split(separator: " ").filter { $0 != "x" })
    let searchableTokens = Set(fields.flatMap { normalize($0.0).split(separator: " ") })
    if !tokens.isEmpty && tokens.allSatisfy({ token in
      searchableTokens.contains(where: { $0.hasPrefix(token) })
    }) {
      return 400
    }
    return nil
  }
}
