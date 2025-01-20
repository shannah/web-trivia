<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Wed, 11 Jan 1984 05:00:00 GMT");
$method = $_SERVER['REQUEST_METHOD'];

// Get the PATH_INFO or fallback to REQUEST_URI for routing
$pathInfo = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : null;
if (!$pathInfo && isset($_SERVER['SCRIPT_URL'])) {
    $pathInfo = $_SERVER['SCRIPT_URL'];
}

if (!$pathInfo) {
    $requestUri = $_SERVER['REQUEST_URI'];
    $scriptName = dirname($_SERVER['SCRIPT_NAME']);
    $pathInfo = str_replace($scriptName, '', parse_url($requestUri, PHP_URL_PATH));
}

// Remove any leading/trailing slashes
$pathInfo = trim($pathInfo, '/');
$path = explode('/', $pathInfo);

requireAuthorization();

// Database connection
$db = new PDO('sqlite:/home/dh_nw4uv8/private/trivia.db');
createTableIfNotExists($db);

// Hardcoded bearer tokens
$validTokens = ["rTWi1hi0BQRD8db9bcKvcQ37J4Ph9hkIEFG1EUUU0cHjfGSGxbAMlVWCOsd2v1pA"];

// Endpoints
if ($path[0] === "import" && $method === "POST") {

    header("Content-Type: application/json");
    requireAuthorization($validTokens);
    handleImport($db);
} elseif ($path[0] === "export" && $method === "GET") {
    header("Content-Type: application/json");
    handleExport($db);
} elseif ($path[0] === "delete" && $method === "DELETE" && isset($path[1])) {
    header("Content-Type: application/json");
    requireAuthorization($validTokens);
    handleDelete($db, $path[1]);
} else {
    header('Content-Type: text/html; charset=utf-8');
    include 'view/index.html';
    exit;
}

/**
 * Ensure authorization for modifying requests.
 */
function requireAuthorization($validTokens = null)
{
    if (!$validTokens) {
        return;
    }

    $headers = getallheaders();

    if (!isset($headers['Authorization']) || !in_array(str_replace('Bearer ', '', $headers['Authorization']), $validTokens)) {
        http_response_code(403);
        echo json_encode(["error" => "Unauthorized"]);
        exit;
    }
}

/**
 * Create trivia table if it doesn't exist.
 */
function createTableIfNotExists($db)
{
    $db->exec("
        CREATE TABLE IF NOT EXISTS trivia (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            category TEXT NOT NULL,
            difficulty TEXT NOT NULL,
            question TEXT NOT NULL,
            answer TEXT NOT NULL
        )
    ");
}

/**
 * Handle importing trivia questions from raw CSV content.
 */
function handleImport($db)
{
    // Ensure the content type is CSV or JSON
    if (!in_array($_SERVER['CONTENT_TYPE'], ['text/csv', 'application/json']) ) {
        http_response_code(400);
        
        echo json_encode(["error" => "Invalid content type. Expected 'text/csv'."]);
        return;
    }

    // Read raw CSV content from the POST body
    $csvContent = file_get_contents("php://input");
    if (!$csvContent) {
        http_response_code(400);
        echo json_encode(["error" => "No CSV content provided."]);
        return;
    }

    try {
        // Parse the CSV content into an array of questions
        if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
            $questions = parseJsonContent($csvContent);
        } else {
            $questions = parseCsvContent($csvContent);
        }
    } catch (\Throwable $exception) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid argument: " . $exception->getMessage()]);
        return;
    }
    


    // Perform "append" operation
    insertQuestions($db, $questions);

    // Success response
    echo json_encode(["success" => "Questions imported successfully."]);
}

function parseJsonContent($jsonContent) {
    $jsonContent = json_decode($jsonContent, true);
    if (array_key_exists('questions', $jsonContent)) {
        $jsonContent = $jsonContent['questions'];
    } else {
        throw new \InvalidArgumentException("No questions received");
    }
    if (!is_array($jsonContent)) {
        return false;
    }
    $out = [];
    foreach ($jsonContent as $row) {
        $out[] = [
            'category' => $row['category'] ?? throw new \InvalidArgumentException('category required'),
            'difficulty' => $row['difficulty'] ?? throw new \InvalidArgumentException('difficulty required'),
            'question' => $row['question'] ?? throw new \InvalidArgumentException('question required'),
            'answer' => $row['answer'] ?? throw new \InvalidArgumentException('answer required'),
        ];
    }
    return $out;
}

/**
 * Parse raw CSV content into an array of questions.
 */
function parseCsvContent($csvContent)
{
    $rows = array_map('str_getcsv', explode("\n", $csvContent));
    $header = array_map('strtolower', array_shift($rows));
    $questions = [];

    foreach ($rows as $row) {
        if (count($row) < count($header)) {
            continue; // Skip incomplete rows
        }

        $row = array_combine($header, $row);
        $questions[] = [
            "category" => $row["category"],
            "difficulty" => strtolower($row["difficulty"]),
            "question" => $row["question"],
            "answer" => $row["answer"]
        ];
    }

    return $questions;
}


/**
 * Handle exporting trivia questions to a CSV file.
 */
function handleExport($db)
{
    $outputFile = "php://output";
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=trivia_export.csv");

    $output = fopen($outputFile, "w");
    fputcsv($output, ["Category", "Difficulty", "Question", "Answer", "Number"]);

    $stmt = $db->query("SELECT * FROM trivia");
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $number = 1;
    foreach ($questions as $question) {
        fputcsv($output, [
            $question["category"],
            ucfirst($question["difficulty"]),
            $question["question"],
            $question["answer"],
            $number++
        ]);
    }

    fclose($output);
}

/**
 * Handle deleting all questions in a specific category.
 */
function handleDelete($db, $category)
{
    $stmt = $db->prepare("DELETE FROM trivia WHERE category = ?");
    $stmt->execute([$category]);

    echo json_encode(["success" => "Questions in category '$category' deleted successfully"]);
}

/**
 * Parse a CSV file into an array of questions.
 */
function parseCsvFile($csvFile)
{
    $rows = array_map('str_getcsv', file($csvFile));
    $header = array_map('strtolower', array_shift($rows));
    $questions = [];

    foreach ($rows as $row) {
        $row = array_combine($header, $row);
        $questions[] = [
            "category" => $row["category"],
            "difficulty" => strtolower($row["difficulty"]),
            "question" => $row["question"],
            "answer" => $row["answer"]
        ];
    }

    return $questions;
}

/**
 * Insert questions into the database.
 */
function insertQuestions($db, $questions)
{
    $stmt = $db->prepare("INSERT INTO trivia (category, difficulty, question, answer) VALUES (?, ?, ?, ?)");
    foreach ($questions as $question) {
        $stmt->execute([
            $question["category"],
            $question["difficulty"],
            $question["question"],
            $question["answer"]
        ]);
    }
}