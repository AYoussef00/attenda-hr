<?php

namespace App\Services;

class JobMatchService
{
    /**
     * Calculate advanced match percentage between job and candidate
     */
    public function calculateMatchPercentage(
        array $jobSkills,
        string $jobTitle,
        array $candidateSkills,
        ?string $candidateTitle,
        ?int $candidateExperienceYears,
        string $cvText = ''
    ): int {
        $scores = [];

        // 1. Skills Matching (40% weight)
        $skillsScore = $this->calculateSkillsMatch($jobSkills, $candidateSkills);
        $scores['skills'] = $skillsScore * 0.40;

        // 2. Job Title Matching (30% weight)
        $titleScore = $this->calculateTitleMatch($jobTitle, $candidateTitle, $cvText);
        $scores['title'] = $titleScore * 0.30;

        // 3. Experience Matching (20% weight)
        $experienceScore = $this->calculateExperienceMatch($jobTitle, $candidateExperienceYears, $cvText);
        $scores['experience'] = $experienceScore * 0.20;

        // 4. Keywords/Context Matching (10% weight)
        $keywordsScore = $this->calculateKeywordsMatch($jobTitle, $jobSkills, $cvText);
        $scores['keywords'] = $keywordsScore * 0.10;

        // Calculate total percentage
        $totalScore = array_sum($scores);

        // Round to nearest integer
        return (int) round($totalScore);
    }

    /**
     * Calculate skills match percentage
     */
    private function calculateSkillsMatch(array $jobSkills, array $candidateSkills): float
    {
        if (empty($jobSkills)) {
            return 0;
        }

        if (empty($candidateSkills)) {
            return 0;
        }

        // Normalize skills to lowercase
        $jobSkillsNormalized = array_map(function($skill) {
            return strtolower(trim($skill));
        }, $jobSkills);

        $candidateSkillsNormalized = array_map(function($skill) {
            return strtolower(trim($skill));
        }, $candidateSkills);

        // Exact matches
        $exactMatches = array_intersect($jobSkillsNormalized, $candidateSkillsNormalized);
        $exactMatchCount = count($exactMatches);

        // Partial matches (fuzzy matching)
        $partialMatches = 0;
        foreach ($jobSkillsNormalized as $jobSkill) {
            foreach ($candidateSkillsNormalized as $candidateSkill) {
                // Check if one skill contains the other (for variations like "JavaScript" vs "JS")
                if ($jobSkill !== $candidateSkill) {
                    if (stripos($jobSkill, $candidateSkill) !== false || 
                        stripos($candidateSkill, $jobSkill) !== false) {
                        $partialMatches++;
                        break;
                    }
                    
                    // Check similarity using Levenshtein distance
                    $similarity = $this->calculateSimilarity($jobSkill, $candidateSkill);
                    if ($similarity > 0.7) {
                        $partialMatches++;
                        break;
                    }
                }
            }
        }

        // Calculate score: exact matches count fully, partial matches count as 0.5
        $totalMatches = $exactMatchCount + ($partialMatches * 0.5);
        $score = ($totalMatches / count($jobSkillsNormalized)) * 100;

        // Cap at 100%
        return min(100, $score);
    }

    /**
     * Calculate title match percentage
     */
    private function calculateTitleMatch(string $jobTitle, ?string $candidateTitle, string $cvText): float
    {
        if (empty($candidateTitle) || $candidateTitle === 'Not Specified') {
            // Try to find title in CV text
            $candidateTitle = $this->extractTitleFromText($jobTitle, $cvText);
        }

        if (empty($candidateTitle)) {
            return 0;
        }

        $jobTitleLower = strtolower(trim($jobTitle));
        $candidateTitleLower = strtolower(trim($candidateTitle));

        // Exact match
        if ($jobTitleLower === $candidateTitleLower) {
            return 100;
        }

        // Check if job title contains candidate title or vice versa
        if (stripos($jobTitleLower, $candidateTitleLower) !== false || 
            stripos($candidateTitleLower, $jobTitleLower) !== false) {
            return 80;
        }

        // Extract key words from titles
        $jobKeywords = $this->extractKeywords($jobTitle);
        $candidateKeywords = $this->extractKeywords($candidateTitle);

        if (empty($jobKeywords) || empty($candidateKeywords)) {
            return 0;
        }

        // Calculate similarity based on common keywords
        $commonKeywords = array_intersect($jobKeywords, $candidateKeywords);
        $similarity = (count($commonKeywords) / max(count($jobKeywords), count($candidateKeywords))) * 100;

        return min(100, $similarity);
    }

    /**
     * Calculate experience match percentage
     */
    private function calculateExperienceMatch(string $jobTitle, ?int $experienceYears, string $cvText): float
    {
        if ($experienceYears === null) {
            return 50; // Neutral score if experience not found
        }

        // Determine required experience based on job title
        $requiredYears = $this->getRequiredExperienceForTitle($jobTitle);

        if ($requiredYears === null) {
            return 50; // Neutral if we can't determine requirement
        }

        // Calculate score based on how close candidate experience is to required
        if ($experienceYears >= $requiredYears) {
            // Candidate has enough or more experience
            $score = 100;
            // Bonus for having more experience (up to 20% bonus)
            if ($experienceYears > $requiredYears) {
                $extraYears = $experienceYears - $requiredYears;
                $bonus = min(20, $extraYears * 5);
                $score = min(100, 100 + $bonus);
            }
        } else {
            // Candidate has less experience
            $ratio = $experienceYears / $requiredYears;
            $score = $ratio * 100;
        }

        return min(100, $score);
    }

    /**
     * Calculate keywords match percentage
     */
    private function calculateKeywordsMatch(string $jobTitle, array $jobSkills, string $cvText): float
    {
        if (empty($cvText)) {
            return 0;
        }

        $cvTextLower = strtolower($cvText);
        $matches = 0;
        $totalKeywords = 0;

        // Check job title keywords in CV
        $titleKeywords = $this->extractKeywords($jobTitle);
        foreach ($titleKeywords as $keyword) {
            $totalKeywords++;
            if (stripos($cvTextLower, strtolower($keyword)) !== false) {
                $matches++;
            }
        }

        // Check job skills in CV
        foreach ($jobSkills as $skill) {
            $totalKeywords++;
            $skillLower = strtolower(trim($skill));
            if (stripos($cvTextLower, $skillLower) !== false) {
                $matches++;
            }
        }

        if ($totalKeywords === 0) {
            return 0;
        }

        return ($matches / $totalKeywords) * 100;
    }

    /**
     * Extract keywords from text
     */
    private function extractKeywords(string $text): array
    {
        // Remove common words
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'from', 'as', 'is', 'was', 'are', 'were', 'been', 'be', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'must', 'can'];
        
        $words = preg_split('/[\s\-_]+/', strtolower($text));
        $keywords = array_filter($words, function($word) use ($stopWords) {
            return strlen($word) > 2 && !in_array($word, $stopWords);
        });

        return array_unique(array_values($keywords));
    }

    /**
     * Calculate similarity between two strings using Levenshtein distance
     */
    private function calculateSimilarity(string $str1, string $str2): float
    {
        $str1 = strtolower($str1);
        $str2 = strtolower($str2);

        $maxLen = max(strlen($str1), strlen($str2));
        if ($maxLen === 0) {
            return 1.0;
        }

        $distance = levenshtein($str1, $str2);
        $similarity = 1 - ($distance / $maxLen);

        return $similarity;
    }

    /**
     * Extract title from CV text based on job title context
     */
    private function extractTitleFromText(string $jobTitle, string $cvText): ?string
    {
        // Try to find similar title in CV
        $jobKeywords = $this->extractKeywords($jobTitle);
        
        // Look for patterns like "Position:", "Role:", "Title:", etc.
        if (preg_match('/(?:position|role|title|current position|job title)[\s:]+([^\n]+)/i', $cvText, $matches)) {
            return trim($matches[1]);
        }

        // Look for common job titles in first 500 characters
        $firstPart = substr($cvText, 0, 500);
        $commonTitles = ['engineer', 'developer', 'manager', 'designer', 'analyst', 'consultant', 'specialist'];
        
        foreach ($commonTitles as $title) {
            if (stripos($firstPart, $title) !== false) {
                // Try to get full title
                if (preg_match('/[A-Z][a-z]+(?:\s+[A-Z][a-z]+)*\s+' . preg_quote($title, '/') . '/i', $firstPart, $matches)) {
                    return trim($matches[0]);
                }
            }
        }

        return null;
    }

    /**
     * Get required experience years based on job title
     */
    private function getRequiredExperienceForTitle(string $jobTitle): ?int
    {
        $titleLower = strtolower($jobTitle);

        // Senior/Lead positions
        if (stripos($titleLower, 'senior') !== false || 
            stripos($titleLower, 'lead') !== false || 
            stripos($titleLower, 'principal') !== false ||
            stripos($titleLower, 'director') !== false) {
            return 5;
        }

        // Mid-level positions
        if (stripos($titleLower, 'mid') !== false || 
            stripos($titleLower, 'intermediate') !== false) {
            return 3;
        }

        // Junior/Entry positions
        if (stripos($titleLower, 'junior') !== false || 
            stripos($titleLower, 'entry') !== false ||
            stripos($titleLower, 'intern') !== false ||
            stripos($titleLower, 'trainee') !== false) {
            return 0;
        }

        // Default: assume mid-level (2-3 years)
        return 2;
    }

    /**
     * Analyze candidate strengths and weaknesses
     */
    public function analyzeCandidate(
        array $jobSkills,
        string $jobTitle,
        array $candidateSkills,
        ?string $candidateTitle,
        ?int $candidateExperienceYears,
        string $cvText = ''
    ): array {
        $strengths = [];
        $weaknesses = [];

        // Analyze Skills
        $skillsAnalysis = $this->analyzeSkills($jobSkills, $candidateSkills);
        $strengths = array_merge($strengths, $skillsAnalysis['strengths']);
        $weaknesses = array_merge($weaknesses, $skillsAnalysis['weaknesses']);

        // Analyze Title
        $titleAnalysis = $this->analyzeTitle($jobTitle, $candidateTitle, $cvText);
        if (!empty($titleAnalysis['strength'])) {
            $strengths[] = $titleAnalysis['strength'];
        }
        if (!empty($titleAnalysis['weakness'])) {
            $weaknesses[] = $titleAnalysis['weakness'];
        }

        // Analyze Experience
        $experienceAnalysis = $this->analyzeExperience($jobTitle, $candidateExperienceYears);
        if (!empty($experienceAnalysis['strength'])) {
            $strengths[] = $experienceAnalysis['strength'];
        }
        if (!empty($experienceAnalysis['weakness'])) {
            $weaknesses[] = $experienceAnalysis['weakness'];
        }

        return [
            'strengths' => $strengths,
            'weaknesses' => $weaknesses,
        ];
    }

    /**
     * Analyze skills match
     */
    private function analyzeSkills(array $jobSkills, array $candidateSkills): array
    {
        $strengths = [];
        $weaknesses = [];

        if (empty($jobSkills)) {
            return ['strengths' => [], 'weaknesses' => []];
        }

        // Normalize skills
        $jobSkillsNormalized = array_map('strtolower', array_map('trim', $jobSkills));
        $candidateSkillsNormalized = array_map('strtolower', array_map('trim', $candidateSkills));

        // Find matching skills
        $matchingSkills = [];
        $missingSkills = [];

        foreach ($jobSkillsNormalized as $jobSkill) {
            $found = false;
            foreach ($candidateSkillsNormalized as $candidateSkill) {
                if ($jobSkill === $candidateSkill || 
                    stripos($jobSkill, $candidateSkill) !== false || 
                    stripos($candidateSkill, $jobSkill) !== false) {
                    $matchingSkills[] = $jobSkill;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $missingSkills[] = $jobSkill;
            }
        }

        // Add strengths
        if (count($matchingSkills) > 0) {
            $strengths[] = 'Has ' . count($matchingSkills) . ' required skill(s): ' . implode(', ', array_map('ucfirst', array_slice($matchingSkills, 0, 5)));
        }

        // Add weaknesses
        if (count($missingSkills) > 0) {
            $weaknesses[] = 'Missing ' . count($missingSkills) . ' required skill(s): ' . implode(', ', array_map('ucfirst', array_slice($missingSkills, 0, 5)));
        }

        // Check if candidate has extra relevant skills
        $extraSkills = array_diff($candidateSkillsNormalized, $jobSkillsNormalized);
        if (count($extraSkills) > 0 && count($matchingSkills) > 0) {
            $strengths[] = 'Has additional relevant skills: ' . implode(', ', array_map('ucfirst', array_slice($extraSkills, 0, 3)));
        }

        return [
            'strengths' => $strengths,
            'weaknesses' => $weaknesses,
        ];
    }

    /**
     * Analyze title match
     */
    private function analyzeTitle(string $jobTitle, ?string $candidateTitle, string $cvText): array
    {
        $strength = null;
        $weakness = null;

        if (empty($candidateTitle) || $candidateTitle === 'Not Specified') {
            $weakness = 'Job title not specified in CV';
            return ['strength' => null, 'weakness' => $weakness];
        }

        $jobTitleLower = strtolower($jobTitle);
        $candidateTitleLower = strtolower($candidateTitle);

        // Extract keywords
        $jobKeywords = $this->extractKeywords($jobTitle);
        $candidateKeywords = $this->extractKeywords($candidateTitle);

        $commonKeywords = array_intersect($jobKeywords, $candidateKeywords);

        if (count($commonKeywords) >= 2 || $jobTitleLower === $candidateTitleLower) {
            $strength = 'Job title closely matches the position: ' . $candidateTitle;
        } elseif (count($commonKeywords) > 0) {
            $strength = 'Job title has some relevance: ' . $candidateTitle;
        } else {
            $weakness = 'Job title may not be a perfect match: ' . $candidateTitle;
        }

        return [
            'strength' => $strength,
            'weakness' => $weakness,
        ];
    }

    /**
     * Analyze experience
     */
    private function analyzeExperience(string $jobTitle, ?int $experienceYears): array
    {
        $strength = null;
        $weakness = null;

        if ($experienceYears === null) {
            $weakness = 'Experience years not specified in CV';
            return ['strength' => null, 'weakness' => $weakness];
        }

        $requiredYears = $this->getRequiredExperienceForTitle($jobTitle);

        if ($requiredYears === null) {
            return ['strength' => null, 'weakness' => null];
        }

        if ($experienceYears >= $requiredYears) {
            if ($experienceYears > $requiredYears + 2) {
                $strength = "Excellent experience: {$experienceYears} years (more than required)";
            } else {
                $strength = "Good experience: {$experienceYears} years (meets requirements)";
            }
        } else {
            $difference = $requiredYears - $experienceYears;
            $weakness = "Experience may be insufficient: {$experienceYears} years (needs {$requiredYears} years, {$difference} year(s) short)";
        }

        return [
            'strength' => $strength,
            'weakness' => $weakness,
        ];
    }
}

