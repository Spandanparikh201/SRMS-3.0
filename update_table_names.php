<?php
/**
 * Script to update table names from capitalized to lowercase format
 * to match the backup file structure
 */

// Define the table name mappings
$tableNameMappings = [
    'School' => 'school',
    'User' => 'user', 
    'Class' => 'class',
    'Subject' => 'subject',
    'Student' => 'student',
    'Teacher' => 'teacher',
    'Teacher_Class_Subject' => 'teacher_class_subject',
    'Result' => 'result',
    'Exam' => 'exam',
    'ExamResult' => 'examresult'
];

// Get all PHP files in the directory
$phpFiles = glob('*.php');

// Exclude this update script itself
$phpFiles = array_filter($phpFiles, function($file) {
    return $file !== 'update_table_names.php';
});

$updatedFiles = [];
$totalReplacements = 0;

foreach ($phpFiles as $file) {
    $content = file_get_contents($file);
    $originalContent = $content;
    $fileReplacements = 0;
    
    // Replace table names in SQL queries
    foreach ($tableNameMappings as $oldName => $newName) {
        // Pattern to match table names in SQL queries
        $patterns = [
            // FROM tablename
            '/\bFROM\s+' . preg_quote($oldName, '/') . '\b/i',
            // JOIN tablename
            '/\bJOIN\s+' . preg_quote($oldName, '/') . '\b/i',
            // INSERT INTO tablename
            '/\bINSERT\s+INTO\s+' . preg_quote($oldName, '/') . '\b/i',
            // UPDATE tablename
            '/\bUPDATE\s+' . preg_quote($oldName, '/') . '\b/i',
            // DELETE FROM tablename
            '/\bDELETE\s+FROM\s+' . preg_quote($oldName, '/') . '\b/i',
            // CREATE TABLE tablename
            '/\bCREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?' . preg_quote($oldName, '/') . '\b/i',
            // DROP TABLE tablename
            '/\bDROP\s+TABLE\s+(?:IF\s+EXISTS\s+)?' . preg_quote($oldName, '/') . '\b/i',
            // ALTER TABLE tablename
            '/\bALTER\s+TABLE\s+' . preg_quote($oldName, '/') . '\b/i',
            // TRUNCATE TABLE tablename
            '/\bTRUNCATE\s+TABLE\s+' . preg_quote($oldName, '/') . '\b/i',
            // REFERENCES tablename
            '/\bREFERENCES\s+' . preg_quote($oldName, '/') . '\b/i',
            // Table name in backticks
            '/`' . preg_quote($oldName, '/') . '`/i',
            // Table name with quotes
            '/"' . preg_quote($oldName, '/') . '"/i',
            '/\'' . preg_quote($oldName, '/') . '\'/i'
        ];
        
        foreach ($patterns as $pattern) {
            $newContent = preg_replace_callback($pattern, function($matches) use ($newName) {
                // Preserve the case of SQL keywords but change table name to lowercase
                return preg_replace('/\b' . preg_quote(substr($matches[0], -strlen($newName)), '/') . '\b/i', $newName, $matches[0]);
            }, $content);
            
            if ($newContent !== $content) {
                $replacements = preg_match_all($pattern, $content);
                $fileReplacements += $replacements;
                $content = $newContent;
            }
        }
    }
    
    // If content was modified, write it back to the file
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        $updatedFiles[] = $file;
        $totalReplacements += $fileReplacements;
        echo "Updated $file - $fileReplacements replacements\n";
    }
}

echo "\n=== UPDATE SUMMARY ===\n";
echo "Total files updated: " . count($updatedFiles) . "\n";
echo "Total replacements made: $totalReplacements\n";
echo "\nUpdated files:\n";
foreach ($updatedFiles as $file) {
    echo "- $file\n";
}

echo "\nTable name mappings applied:\n";
foreach ($tableNameMappings as $old => $new) {
    echo "- $old → $new\n";
}

echo "\nUpdate completed successfully!\n";
?>