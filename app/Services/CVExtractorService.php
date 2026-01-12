<?php

namespace App\Services;

use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser;

class CVExtractorService
{
    /**
     * Extract data from CV file
     */
    public function extractData($file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $text = '';

        try {
            if ($extension === 'pdf') {
                $text = $this->extractFromPDF($file);
            } elseif (in_array($extension, ['doc', 'docx'])) {
                $text = $this->extractFromWord($file);
            }
        } catch (\Exception $e) {
            \Log::error('CV Extraction Error: ' . $e->getMessage());
        }

        $data = $this->parseText($text);
        
        // Log extracted data for debugging
        \Log::info('CV Extracted Data', [
            'name' => $data['name'] ?? 'NOT FOUND',
            'email' => $data['email'] ?? 'NOT FOUND',
            'phone' => $data['phone'] ?? 'NOT FOUND',
            'title' => $data['title'] ?? 'NOT FOUND',
            'text_length' => strlen($text),
            'first_100_chars' => substr($text, 0, 100),
        ]);

        return $data;
    }

    /**
     * Extract text from PDF
     */
    private function extractFromPDF($file): string
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($file->getRealPath());
        return $pdf->getText();
    }

    /**
     * Extract text from Word document
     */
    private function extractFromWord($file): string
    {
        $phpWord = IOFactory::load($file->getRealPath());
        $text = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text .= $element->getText() . "\n";
                }
            }
        }

        return $text;
    }

    /**
     * Parse extracted text to extract candidate information
     */
    private function parseText(string $text): array
    {
        $data = [
            'name' => $this->extractName($text),
            'email' => $this->extractEmail($text),
            'phone' => $this->extractPhone($text),
            'title' => $this->extractTitle($text),
            'skills' => $this->extractSkills($text),
            'experience_years' => $this->extractExperienceYears($text),
        ];

        return $data;
    }

    /**
     * Extract name (usually first line or after "Name:")
     */
    private function extractName(string $text): ?string
    {
        $lines = array_map('trim', explode("\n", trim($text)));
        $lines = array_filter($lines, function($line) {
            return !empty($line) && strlen($line) > 2;
        });
        $lines = array_values($lines);
        
        // Try to find name after "Name:" or "Full Name:"
        if (preg_match('/(?:name|full name|الاسم)[\s:]+([A-Z][a-z]+(?:\s+[A-Z][a-z]+)+)/i', $text, $matches)) {
            $name = trim($matches[1]);
            if (strlen($name) > 3 && strlen($name) < 100) {
                return $name;
            }
        }

        // Try to extract from email if available
        if (preg_match('/([a-zA-Z0-9._%+-]+)@/', $text, $emailMatches)) {
            $emailName = $emailMatches[1];
            // Try to extract name from email (e.g., abdelrahman.youseff -> Abdelrahman Youseff)
            if (preg_match('/^([a-z]+)\.([a-z]+)$/i', $emailName, $nameMatches)) {
                $firstName = ucfirst($nameMatches[1]);
                $lastName = ucfirst($nameMatches[2]);
                return $firstName . ' ' . $lastName;
            }
        }

        // Try first few lines if they look like a name
        for ($i = 0; $i < min(3, count($lines)); $i++) {
            $line = $lines[$i];
            
            // Skip common CV headers
            if (preg_match('/^(resume|cv|curriculum vitae|summary|objective|experience|education|skills|contact|phone|email|address)$/i', $line)) {
                continue;
            }
            
            // Check if line looks like a name (2-4 words, each starting with capital letter)
            if (preg_match('/^([A-Z][a-z]+(?:\s+[A-Z][a-z]+){1,3})$/', $line, $matches)) {
                $name = trim($matches[1]);
                // Exclude if it's too long (probably not a name) or contains common non-name words
                if (strlen($name) > 3 && strlen($name) < 60 && 
                    !preg_match('/(years|experience|university|college|company|position|role|job)/i', $name)) {
                    return $name;
                }
            }
            
            // Try pattern: First Last or First Middle Last (more flexible)
            if (preg_match('/^([A-Z][a-z]+\s+[A-Z][a-z]+(?:\s+[A-Z][a-z]+)?)$/', $line, $matches)) {
                $name = trim($matches[1]);
                if (strlen($name) > 5 && strlen($name) < 60) {
                    // Additional check: make sure it doesn't contain numbers or special chars
                    if (!preg_match('/[0-9@#$%^&*()_+=\[\]{}|;:,.<>?\/]/', $name)) {
                        return $name;
                    }
                }
            }
        }

        // Try to find name pattern anywhere in first 500 characters
        $firstPart = substr($text, 0, 500);
        if (preg_match('/^([A-Z][a-z]+\s+[A-Z][a-z]+(?:\s+[A-Z][a-z]+)?)/m', $firstPart, $matches)) {
            $name = trim($matches[1]);
            if (strlen($name) > 5 && strlen($name) < 60 && 
                !preg_match('/(years|experience|university|college|company|position|role|job|email|phone|address|summary|objective)/i', $name)) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Extract email
     */
    private function extractEmail(string $text): ?string
    {
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $text, $matches)) {
            return $matches[0];
        }

        return null;
    }

    /**
     * Extract phone number
     */
    private function extractPhone(string $text): ?string
    {
        // Match various phone formats
        $patterns = [
            '/\+?[0-9]{1,4}[\s-]?\(?[0-9]{1,4}\)?[\s-]?[0-9]{1,4}[\s-]?[0-9]{1,9}/',
            '/\+?[0-9]{10,15}/',
            '/\(?[0-9]{3}\)?[\s-]?[0-9]{3}[\s-]?[0-9]{4}/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return trim($matches[0]);
            }
        }

        return null;
    }

    /**
     * Extract job title
     */
    private function extractTitle(string $text): ?string
    {
        $titleKeywords = [
            'position', 'title', 'role', 'job title', 'current position',
            'profession', 'occupation', 'designation'
        ];

        foreach ($titleKeywords as $keyword) {
            if (preg_match('/' . preg_quote($keyword, '/') . '[\s:]+([^\n]+)/i', $text, $matches)) {
                $title = trim($matches[1]);
                // Clean up the title
                $title = preg_replace('/[^\w\s-]/', '', $title);
                if (strlen($title) > 3 && strlen($title) < 100) {
                    return $title;
                }
            }
        }

        // Try to find common job titles in the text
        $commonTitles = [
            'Software Engineer', 'Developer', 'Manager', 'Designer',
            'Analyst', 'Consultant', 'Specialist', 'Coordinator',
            'Director', 'Executive', 'Administrator', 'Assistant'
        ];

        foreach ($commonTitles as $title) {
            if (stripos($text, $title) !== false) {
                // Try to get the full title
                if (preg_match('/[A-Z][a-z]+(?:\s+[A-Z][a-z]+)*\s+' . preg_quote($title, '/') . '/i', $text, $matches)) {
                    return trim($matches[0]);
                }
                return $title;
            }
        }

        return null;
    }

    /**
     * Extract skills
     */
    private function extractSkills(string $text): array
    {
        $skills = [];
        
        // Common skills keywords
        $skillKeywords = [
            'skills', 'technical skills', 'competencies', 'expertise',
            'proficiencies', 'abilities', 'technologies', 'technologies used',
            'programming languages', 'tools', 'software', 'frameworks',
            'languages', 'platforms', 'certifications'
        ];

        foreach ($skillKeywords as $keyword) {
            if (preg_match('/' . preg_quote($keyword, '/') . '[\s:]+([^\n]+(?:\n[^\n]+){0,10})/i', $text, $matches)) {
                $skillsText = $matches[1];
                // Extract skills (usually separated by commas, bullets, colons, semicolons, or new lines)
                $skillsList = preg_split('/[,•\-\n\r;:|\/]+/', $skillsText);
                foreach ($skillsList as $skill) {
                    $skill = trim($skill);
                    // Clean up skill
                    $skill = preg_replace('/^\W+|\W+$/', '', $skill); // Remove leading/trailing punctuation
                    if (strlen($skill) > 2 && strlen($skill) < 50 && 
                        !preg_match('/^(years|experience|proficient|expert|beginner|intermediate|advanced)$/i', $skill)) {
                        $skills[] = $skill;
                    }
                }
                break;
            }
        }

        // Also try to extract common technical skills from the entire text
        $commonTechSkills = [
            'JavaScript', 'Python', 'Java', 'PHP', 'C++', 'C#', 'Ruby', 'Go', 'Swift', 'Kotlin',
            'React', 'Vue', 'Angular', 'Node.js', 'Laravel', 'Django', 'Spring', 'Express',
            'MySQL', 'PostgreSQL', 'MongoDB', 'Redis', 'SQL Server',
            'AWS', 'Azure', 'Docker', 'Kubernetes', 'Git', 'GitHub', 'GitLab',
            'HTML', 'CSS', 'SASS', 'Bootstrap', 'Tailwind',
            'REST API', 'GraphQL', 'Microservices', 'Agile', 'Scrum',
            'Photoshop', 'Illustrator', 'Figma', 'Sketch',
            'Excel', 'PowerPoint', 'Word', 'Project Management'
        ];

        $textLower = strtolower($text);
        foreach ($commonTechSkills as $skill) {
            if (stripos($textLower, strtolower($skill)) !== false) {
                if (!in_array($skill, $skills)) {
                    $skills[] = $skill;
                }
            }
        }

        // Limit to 30 skills max
        return array_slice(array_unique($skills), 0, 30);
    }

    /**
     * Extract years of experience
     */
    private function extractExperienceYears(string $text): ?int
    {
        // Look for patterns like "5 years", "5+ years", "5 years of experience"
        if (preg_match('/(\d+)\+?\s*(?:years?|yrs?)\s*(?:of\s*)?(?:experience|exp)/i', $text, $matches)) {
            return (int) $matches[1];
        }

        // Look for date ranges in experience section
        if (preg_match_all('/(\d{4})\s*[-–]\s*(\d{4}|present|current)/i', $text, $matches)) {
            $years = 0;
            foreach ($matches[1] as $index => $startYear) {
                $endYear = $matches[2][$index];
                if (strtolower($endYear) === 'present' || strtolower($endYear) === 'current') {
                    $endYear = date('Y');
                }
                $years += (int) $endYear - (int) $startYear;
            }
            if ($years > 0) {
                return $years;
            }
        }

        return null;
    }
}

