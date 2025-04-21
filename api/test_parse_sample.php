<?php
/**
 * Test script to directly parse the sample HTML row
 */

// Include shipment_db_config.php
require_once __DIR__ . '/shipment_db_config.php';

// Enable error reporting for development
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Sample table row data from WebTracker
$sampleHtml = '<tr class="DetailsHeader">
<td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl00\',\'\')">Shipment#</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl01\',\'\')">Bill</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl02\',\'\')">Shipper</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl03\',\'\')">Consignee</a></td><td>Origin</td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl04\',\'\')">ETD</a></td><td>Destination</td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl05\',\'\')">ETA</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl06\',\'\')">Declaration Country/Region</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl07\',\'\')">Actual Pickup</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl08\',\'\')">Additional Terms</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl09\',\'\')">Booked Online</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl10\',\'\')">Charges</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl11\',\'\')">Charges Apply</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl12\',\'\')">Consignee Address</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl13\',\'\')">Consignee City</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl14\',\'\')">Consignee Full Address</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl15\',\'\')">Consignee Post Code</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl16\',\'\')">Consignee State</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl17\',\'\')">Container Mode</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl18\',\'\')">Containers</a></td><td>Currency</td><td>Current Discharge Port</td><td>Current Load Port</td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl19\',\'\')">Current Vessel</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl20\',\'\')">Current Voy./Flight</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl21\',\'\')">Delivery Agent</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl22\',\'\')">Delivery Date</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl23\',\'\')">Delivery Required By</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl24\',\'\')">Estimated Delivery</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl25\',\'\')">Estimated Pickup</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl26\',\'\')">First Leg Load ATD</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl27\',\'\')">First Leg Load ETD</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl28\',\'\')">Goods Description</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl29\',\'\')">Goods Value</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl30\',\'\')">Inspection</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl31\',\'\')">Job Notes</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl32\',\'\')">Last Leg Discharge ATA</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl33\',\'\')">Last Leg Discharge ETA</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl34\',\'\')">Loading Meters</a></td><td>Main Discharge Port</td><td>Main Load Port</td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl35\',\'\')">Main Vessel</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl36\',\'\')">Main Voy./Flight</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl37\',\'\')">Mode</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl38\',\'\')">On Board</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl39\',\'\')">Order Ref#</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl40\',\'\')">Owner\'s Ref#</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl41\',\'\')">Packs</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl42\',\'\')">Payment Term</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl43\',\'\')">Pickup Agent</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl44\',\'\')">Pickup Required By</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl45\',\'\')">Pieces Received</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl46\',\'\')">Received By</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl47\',\'\')">Received Date</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl48\',\'\')">Release Type</a></td><td>Service Level</td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl49\',\'\')">Shipper Address</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl50\',\'\')">Shipper City</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl51\',\'\')">Shipper Full Address</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl52\',\'\')">Shipper Post Code</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl53\',\'\')">Shipper State</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl54\',\'\')">Shipper\'s Ref#</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl55\',\'\')">Storage Commences</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl56\',\'\')">TEU</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl57\',\'\')">Type</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl58\',\'\')">Volume</a></td><td><a href="javascript:__doPostBack(\'ctl07$SearchResultsDataGridController$SearchResultsDataGrid$ctl02$ctl59\',\'\')">Weight</a></td>
</tr>
<tr>
<td>STUT00184199</td><td>STUT00184199</td><td>AMMAN GRANITES</td><td>ANGEL STONES LLC</td><td>Tuticorin</td><td>09-Feb-25 00:00</td><td>Savannah</td><td>31-Mar-25 06:00</td><td></td><td></td><td></td><td>N</td><td>USD 4,502.03</td><td>NON</td><td>554O CENTERVIEW STE 204 ,PMB 162836,</td><td>RALEIGH</td><td>"ANGEL STONES LLC, 
554O CENTERVIEW STE 204 ,PMB 162836, RALEIGH NC 27606"</td><td>27606</td><td>NC</td><td>FCL</td><td>MEDU3902167</td><td></td><td>Savannah</td><td>Colombo</td><td>MSC BARCELONA</td><td>IU505A</td><td>DAHNAY LOGISTICS USA INC</td><td></td><td></td><td></td><td></td><td>09-Feb-25</td><td>09-Feb-25</td><td>POLISED GRANITE MOUNTENTS</td><td>0.00</td><td>UNK</td><td></td><td>30-Mar-25</td><td>31-Mar-25</td><td>0.00</td><td>Savannah</td><td>Colombo</td><td>MSC BARCELONA</td><td>IU505A</td><td>SEA</td><td>SHP</td><td></td><td></td><td>57 PKG</td><td>FOB</td><td></td><td></td><td>0</td><td></td><td></td><td>SWB</td><td></td><td>M.G.COLONY,HARUR, DHARMAPURI DISTRICT</td><td>DHARMAPURI</td><td>"AMMAN GRANITES, 
M.G.COLONY,HARUR DHARMAPURI DISTRICT DHARMAPURI TN 636903 INDIA"</td><td>636903</td><td>TN</td><td></td><td>31-Mar-25</td><td>1.00</td><td>STD</td><td>0.000 M3</td><td>21000.000 KG</td>
</tr>';

// Map of headers to database column names
$headerToColumnMap = [
    'Shipment#' => 'shipment_number',
    'Bill' => 'bill',
    'Shipper' => 'shipper',
    'Consignee' => 'consignee',
    'Origin' => 'origin',
    'ETD' => 'etd',
    'Destination' => 'destination',
    'ETA' => 'eta',
    'Declaration Country/Region' => 'declaration_country',
    'Actual Pickup' => 'actual_pickup',
    'Additional Terms' => 'additional_terms',
    'Booked Online' => 'booked_online',
    'Charges' => 'charges',
    'Charges Apply' => 'charges_apply',
    'Consignee Address' => 'consignee_address',
    'Consignee City' => 'consignee_city',
    'Consignee Full Address' => 'consignee_full_address',
    'Consignee Post Code' => 'consignee_post_code',
    'Consignee State' => 'consignee_state',
    'Container Mode' => 'container_mode',
    'Containers' => 'containers',
    'Currency' => 'currency',
    'Current Discharge Port' => 'current_discharge_port',
    'Current Load Port' => 'current_load_port',
    'Current Vessel' => 'current_vessel',
    'Current Voy./Flight' => 'current_voy_flight',
    'Delivery Agent' => 'delivery_agent',
    'Delivery Date' => 'delivery_date',
    'Delivery Required By' => 'delivery_required_by',
    'Estimated Delivery' => 'estimated_delivery',
    'Estimated Pickup' => 'estimated_pickup',
    'First Leg Load ATD' => 'first_leg_load_atd',
    'First Leg Load ETD' => 'first_leg_load_etd',
    'Goods Description' => 'goods_description',
    'Goods Value' => 'goods_value',
    'Inspection' => 'inspection',
    'Job Notes' => 'job_notes',
    'Last Leg Discharge ATA' => 'last_leg_discharge_ata',
    'Last Leg Discharge ETA' => 'last_leg_discharge_eta',
    'Loading Meters' => 'loading_meters',
    'Main Discharge Port' => 'main_discharge_port',
    'Main Load Port' => 'main_load_port',
    'Main Vessel' => 'main_vessel',
    'Main Voy./Flight' => 'main_voy_flight',
    'Mode' => 'mode',
    'On Board' => 'on_board',
    'Order Ref#' => 'order_ref',
    'Owner\'s Ref#' => 'owners_ref',
    'Packs' => 'packs',
    'Payment Term' => 'payment_term',
    'Pickup Agent' => 'pickup_agent',
    'Pickup Required By' => 'pickup_required_by',
    'Pieces Received' => 'pieces_received',
    'Received By' => 'received_by',
    'Received Date' => 'received_date',
    'Release Type' => 'release_type',
    'Service Level' => 'service_level',
    'Shipper Address' => 'shipper_address',
    'Shipper City' => 'shipper_city',
    'Shipper Full Address' => 'shipper_full_address',
    'Shipper Post Code' => 'shipper_post_code',
    'Shipper State' => 'shipper_state',
    'Shipper\'s Ref#' => 'shippers_ref',
    'Storage Commences' => 'storage_commences',
    'TEU' => 'teu',
    'Type' => 'type',
    'Volume' => 'volume',
    'Weight' => 'weight'
];

echo "Parsing sample data...\n";

// Create a DOM document
$dom = new DOMDocument();
@$dom->loadHTML($sampleHtml);
$xpath = new DOMXPath($dom);

// Find rows
$rows = $xpath->query('//tr');
if ($rows->length < 2) {
    echo "Error: Not enough rows found in sample data\n";
    exit(1);
}

// First row is header, second row is data
$headerRow = $rows->item(0);
$dataRow = $rows->item(1);

// Extract headers
$headerCells = $xpath->query('.//td', $headerRow);
$headers = [];
for ($i = 0; $i < $headerCells->length; $i++) {
    $headerText = trim(strip_tags($headerCells->item($i)->textContent));
    $headers[$i] = $headerText;
    echo "Header[$i]: $headerText\n";
}

// Extract data
$dataCells = $xpath->query('.//td', $dataRow);
$shipment = [];

for ($i = 0; $i < $dataCells->length; $i++) {
    $value = trim($dataCells->item($i)->textContent);
    
    if (isset($headers[$i]) && isset($headerToColumnMap[$headers[$i]])) {
        $columnName = $headerToColumnMap[$headers[$i]];
        $shipment[$columnName] = $value;
        echo "Data[$i]: $headers[$i] = $value (Column: $columnName)\n";
    }
}

// Connect to database
$pdo = getShipmentDbConnection();

// Insert or update the shipment
$columns = ['shipment_number', 'last_updated'];
$values = [$shipment['shipment_number'], date('Y-m-d H:i:s')];
$placeholders = ['?', '?'];
$updatePairs = ['last_updated = ?'];

// Add all shipment fields
foreach ($shipment as $key => $value) {
    if ($key !== 'shipment_number') {
        $columns[] = $key;
        $values[] = $value;
        $placeholders[] = '?';
        $updatePairs[] = "$key = ?";
    }
}

// Prepare SQL
$sql = "INSERT INTO shipment_tracking (" . implode(',', $columns) . ") 
        VALUES (" . implode(',', $placeholders) . ")
        ON DUPLICATE KEY UPDATE " . implode(', ', $updatePairs);

echo "\nSQL: $sql\n";
echo "Columns: " . implode(', ', $columns) . "\n";
echo "Values: " . implode(', ', $values) . "\n";

// Execute the query
try {
    // All values for the INSERT, plus the update values
    $allValues = $values;
    for ($i = 1; $i < count($values); $i++) {
        $allValues[] = $values[$i];
    }
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($allValues);
    
    if ($result) {
        echo "Successfully inserted/updated shipment STUT00184199\n";
    } else {
        echo "Error inserting/updating shipment\n";
    }
} catch (PDOException $e) {
    echo "SQL Error: " . $e->getMessage() . "\n";
}

// Verify the data was stored correctly
echo "\nVerifying data in database...\n";
$verifyStmt = $pdo->prepare("SELECT shipment_number, shipper, consignee, charges FROM shipment_tracking WHERE shipment_number = ?");
$verifyStmt->execute([$shipment['shipment_number']]);
$result = $verifyStmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    echo "Shipment: " . $result['shipment_number'] . "\n";
    echo "Shipper: " . $result['shipper'] . "\n";
    echo "Consignee: " . $result['consignee'] . "\n";
    echo "Charges: " . $result['charges'] . "\n";
} else {
    echo "Unable to verify data in database\n";
}

echo "\nDone\n";
