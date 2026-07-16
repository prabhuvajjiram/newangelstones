package com.angelgranites.app

import androidx.appfunctions.AppFunction
import androidx.appfunctions.AppFunctionSerializable
import androidx.appfunctions.AppFunctionService
import androidx.appfunctions.AppFunctionServiceEntryPoint
import java.net.HttpURLConnection
import java.net.URL
import java.net.URLEncoder
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import org.json.JSONObject

/** Natural-language inventory tools exposed to Android assistants such as Gemini. */
@AppFunctionServiceEntryPoint(
    serviceName = "InventoryAppFunctionService",
    appFunctionXmlFileName = "inventory_app_functions",
)
abstract class InventoryAppFunctions : AppFunctionService() {
    /** A design, color, type, or size to find in live Angel Granites inventory. */
    @AppFunctionSerializable(isDescribedByKDoc = true)
    data class InventoryQuery(
        /** Examples: AG-298, heart headstone, 0-8 thickness, or 4-0 x 0-8 x 2-4. */
        val query: String,
    )

    /** A live availability answer and a safe link to inspect matching stock in the app. */
    @AppFunctionSerializable(isDescribedByKDoc = true)
    data class InventoryAvailability(
        /** A concise, human-readable availability answer. */
        val summary: String,
        /** Number of matching records with available quantity. */
        val matchingRecords: Int,
        /** Total available pieces across matching records. */
        val totalQuantity: Int,
        /** Verified Angel Stones App Link that opens the matching inventory search. */
        val deepLink: String,
    )

    /**
     * Checks live Angel Granites inventory by monument design, stone color, product type,
     * thickness, or full dimensions. This is read-only and never creates an order or quote.
     *
     * @param inventoryQuery the stock description expressed by the user
     */
    @AppFunction(isDescribedByKDoc = true)
    suspend fun checkInventoryAvailability(
        inventoryQuery: InventoryQuery,
    ): InventoryAvailability = withContext(Dispatchers.IO) {
        InventoryAvailabilityClient.check(inventoryQuery.query)
    }
}

private object InventoryAvailabilityClient {
    private const val endpoint = "https://theangelstones.com/inventory-proxy.php"
    private const val cacheTtlMillis = 2 * 60 * 60 * 1000L
    private val fillerWords = setOf(
        "a", "all", "any", "are", "available", "availability", "can", "could",
        "do", "does", "find", "for", "full", "have", "in", "inventory", "is",
        "me", "of", "please", "search", "show", "stock", "stone", "stones",
        "the", "there", "thick", "thickness", "with", "you",
    )

    private data class Record(
        val quantity: Int,
        val code: String,
        val description: String,
        val color: String,
        val size: String,
        val location: String,
        val type: String,
        val design: String,
        val finish: String,
    )

    private data class RankedRecord(val record: Record, val score: Int)

    @Volatile private var cachedAt = 0L
    @Volatile private var cachedRecords: List<Record> = emptyList()

    fun check(rawQuery: String): InventoryAppFunctions.InventoryAvailability {
        val query = searchTerms(rawQuery)
        val deepLink = "$endpointBase/app/inventory?query=${encode(rawQuery.trim())}"
        if (query.isEmpty()) {
            return InventoryAppFunctions.InventoryAvailability(
                summary = "Provide a design, stone color, product type, or monument size to check.",
                matchingRecords = 0,
                totalQuantity = 0,
                deepLink = deepLink,
            )
        }

        val matches = inventory().mapNotNull { record ->
            score(record, query)?.let { RankedRecord(record, it) }
        }.sortedWith(
            compareBy<RankedRecord> { it.score }
                .thenByDescending { it.record.quantity }
                .thenBy { it.record.description },
        )
        val available = matches.filter { it.record.quantity > 0 }
        val totalQuantity = available.sumOf { it.record.quantity }
        val best = matches.firstOrNull()?.record

        val summary = when {
            best == null ->
                "No live stock matched $rawQuery. Try a design code, color, type, or full size."
            totalQuantity == 0 ->
                "Found ${matches.size} matching stock records for $rawQuery, but none currently have available quantity."
            else -> {
                val label = best.design.ifBlank { best.description }
                val details = listOf(best.color, best.size, best.location)
                    .filter { it.isNotBlank() }
                    .joinToString()
                "Yes. Found ${available.size} matching stock records totaling $totalQuantity pieces. " +
                    "The best match is $label, $details, with ${best.quantity} available."
            }
        }

        return InventoryAppFunctions.InventoryAvailability(
            summary = summary,
            matchingRecords = available.size,
            totalQuantity = totalQuantity,
            deepLink = deepLink,
        )
    }

    private const val endpointBase = "https://theangelstones.com"

    private fun inventory(): List<Record> {
        val now = System.currentTimeMillis()
        if (cachedRecords.isNotEmpty() && now - cachedAt < cacheTtlMillis) {
            return cachedRecords
        }

        val records = mutableListOf<Record>()
        var page = 1
        var hasNextPage = true
        while (hasNextPage && page <= 10) {
            val parameters = linkedMapOf(
                "hasdesc" to "false",
                "description" to "",
                "ptype" to "",
                "pcolor" to "",
                "pdesign" to "",
                "pfinish" to "",
                "psize" to "",
                "locid" to "",
                "page" to page.toString(),
                "pageSize" to "1000",
            )
            val queryString = parameters.entries.joinToString("&") {
                "${encode(it.key)}=${encode(it.value)}"
            }
            val connection = URL("$endpoint?$queryString").openConnection() as HttpURLConnection
            try {
                connection.requestMethod = "GET"
                connection.connectTimeout = 15_000
                connection.readTimeout = 30_000
                connection.setRequestProperty("User-Agent", "AngelGranites-Mobile-App/1.0")
                connection.setRequestProperty("Accept", "application/json")
                require(connection.responseCode in 200..299) {
                    "Inventory service returned ${connection.responseCode}"
                }

                val response = connection.inputStream.bufferedReader().use { it.readText() }
                val payload = JSONObject(response)
                val data = payload.optJSONArray("data")
                if (data != null) {
                    for (index in 0 until data.length()) {
                        val item = data.getJSONObject(index)
                        records += Record(
                            quantity = item.optInt("Qty"),
                            code = item.optString("EndProductCode"),
                            description = item.optString("EndProductDescription"),
                            color = item.optString("PColor"),
                            size = item.optString("Size"),
                            location = item.optString("Locationname"),
                            type = item.optString("Ptype"),
                            design = item.optString("PDesign"),
                            finish = item.optString("PFinish"),
                        )
                    }
                }
                hasNextPage = payload.optJSONObject("pagination")
                    ?.optBoolean("hasNextPage") == true
                page += 1
            } finally {
                connection.disconnect()
            }
        }

        cachedRecords = records
        cachedAt = now
        return records
    }

    private fun normalize(value: String): String = value
        .lowercase()
        .replace('×', 'x')
        .replace(Regex("[^a-z0-9]+"), " ")
        .trim()
        .replace(Regex("\\s+"), " ")

    private fun searchTerms(value: String): String {
        val normalizedHeadstone = value.replace(
            Regex("\\bhead\\s+stones?\\b", RegexOption.IGNORE_CASE),
            "headstone",
        )
        val terms = normalize(normalizedHeadstone).split(' ')
            .filter { it == "x" || it !in fillerWords }
            .toMutableList()
        if (terms.remove("headstone") && terms.all { it == "x" }) terms += "tablet"
        return terms.joinToString(" ").trim()
    }

    private fun compact(value: String): String = normalize(value).replace(" ", "")

    private fun dimensionSignature(value: String): List<String>? {
        val parts = normalize(value).split('x').map { it.replace(" ", "") }
        return parts.takeIf { it.size == 3 && it.all(String::isNotEmpty) }?.sorted()
    }

    private fun score(record: Record, query: String): Int? {
        val compactQuery = compact(query)
        val queryDimensions = dimensionSignature(query)
        if (queryDimensions != null && queryDimensions == dimensionSignature(record.size)) return 0

        val fields = listOf(
            record.size to 0,
            record.code to 10,
            record.design to 20,
            record.color to 30,
            record.type to 40,
            record.finish to 50,
            record.description to 60,
            record.location to 70,
        )
        var best: Int? = null
        for ((value, priority) in fields) {
            if (value.isBlank()) continue
            val normalizedValue = normalize(value)
            val compactValue = compact(value)
            val candidate = when {
                normalizedValue == query || compactValue == compactQuery -> priority
                normalizedValue.startsWith(query) || compactValue.startsWith(compactQuery) ->
                    100 + priority
                normalizedValue.contains(query) || compactValue.contains(compactQuery) ->
                    200 + priority
                else -> null
            }
            if (candidate != null && (best == null || candidate < best)) best = candidate
        }
        if (best != null) return best

        val tokens = query.split(' ').filter { it.isNotBlank() && it != "x" }.toSet()
        val searchableTokens = fields.flatMap { normalize(it.first).split(' ') }.toSet()
        return if (tokens.isNotEmpty() && tokens.all { token ->
                searchableTokens.any { it.startsWith(token) }
            }
        ) 400 else null
    }

    private fun encode(value: String): String = URLEncoder.encode(value, Charsets.UTF_8.name())
}
