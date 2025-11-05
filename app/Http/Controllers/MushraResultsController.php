<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MushraResultsController extends Controller
{
    /**
     * Store webMUSHRA test results
     */
    public function store(Request $request)
    {
        // Ensure user is authenticated
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Get session data from POST
        $sessionJSON = $request->input('sessionJSON');
        $session = json_decode($sessionJSON);

        if (!$session) {
            return response()->json(['error' => 'Invalid session data'], 400);
        }

        // Add user information to session
        $session->userId = Auth::id();
        $session->userEmail = Auth::user()->email;

        // Sanitize test ID
        $testId = $this->sanitize($session->testId);

        // Create results directory
        $resultsPath = "mushra-results/{$testId}";
        if (!Storage::disk('local')->exists($resultsPath)) {
            Storage::disk('local')->makeDirectory($resultsPath);
        }

        $length = count($session->participant->name ?? []);

        // Process different test types
        $this->processMushra($session, $resultsPath, $length);
        $this->processPairedComparison($session, $resultsPath, $length);
        $this->processBs1116($session, $resultsPath, $length);
        $this->processLikertMultiStimulus($session, $resultsPath, $length);
        $this->processLikertSingleStimulus($session, $resultsPath, $length);
        $this->processSpatialLocalization($session, $resultsPath, $length);
        $this->processSpatialAsw($session, $resultsPath, $length);
        $this->processSpatialHwd($session, $resultsPath, $length);
        $this->processSpatialLev($session, $resultsPath, $length);

        return response()->json(['success' => true, 'message' => 'Results saved successfully']);
    }

    private function sanitize($string, $isFilename = false)
    {
        $string = preg_replace('/[^\w\-' . ($isFilename ? '~_\.' : '') . ']+/u', '-', $string);
        return strtolower(preg_replace('/--+/u', '-', $string));
    }

    private function processMushra($session, $resultsPath, $length)
    {
        $csvData = [];
        $writeData = false;

        $input = array_merge(["session_test_id", "user_id", "user_email"],
            array_map(fn($i) => $session->participant->name[$i] ?? "field_$i", range(0, $length - 1)),
            ["session_uuid", "trial_id", "rating_stimulus", "rating_score", "rating_time", "rating_comment"]);
        array_push($csvData, $input);

        foreach ($session->trials as $trial) {
            if ($trial->type == "mushra") {
                $writeData = true;
                foreach ($trial->responses as $response) {
                    $results = [$session->testId, $session->userId, $session->userEmail];
                    for ($i = 0; $i < $length; $i++) {
                        array_push($results, $session->participant->response[$i] ?? '');
                    }
                    array_push($results, $session->uuid, $trial->id, $response->stimulus,
                        $response->score, $response->time, $response->comment ?? '');
                    array_push($csvData, $results);
                }
            }
        }

        if ($writeData) {
            $this->appendCsv("$resultsPath/mushra.csv", $csvData);
        }
    }

    private function processPairedComparison($session, $resultsPath, $length)
    {
        $csvData = [];
        $writeData = false;

        $input = array_merge(["session_test_id", "user_id", "user_email"],
            array_map(fn($i) => $session->participant->name[$i] ?? "field_$i", range(0, $length - 1)),
            ["trial_id", "choice_reference", "choice_non_reference", "choice_answer", "choice_time", "choice_comment"]);
        array_push($csvData, $input);

        foreach ($session->trials as $trial) {
            if ($trial->type == "paired_comparison") {
                foreach ($trial->responses as $response) {
                    $writeData = true;
                    $results = [$session->testId, $session->userId, $session->userEmail];
                    for ($i = 0; $i < $length; $i++) {
                        array_push($results, $session->participant->response[$i] ?? '');
                    }
                    array_push($results, $trial->id, $response->reference, $response->nonReference,
                        $response->answer, $response->time, $response->comment ?? '');
                    array_push($csvData, $results);
                }
            }
        }

        if ($writeData) {
            $this->appendCsv("$resultsPath/paired_comparison.csv", $csvData);
        }
    }

    private function processBs1116($session, $resultsPath, $length)
    {
        $csvData = [];
        $writeData = false;

        $input = array_merge(["session_test_id", "user_id", "user_email"],
            array_map(fn($i) => $session->participant->name[$i] ?? "field_$i", range(0, $length - 1)),
            ["trial_id", "rating_reference", "rating_non_reference", "rating_reference_score",
             "rating_non_reference_score", "rating_time", "choice_comment"]);
        array_push($csvData, $input);

        foreach ($session->trials as $trial) {
            if ($trial->type == "bs1116") {
                foreach ($trial->responses as $response) {
                    $writeData = true;
                    $results = [$session->testId, $session->userId, $session->userEmail];
                    for ($i = 0; $i < $length; $i++) {
                        array_push($results, $session->participant->response[$i] ?? '');
                    }
                    array_push($results, $trial->id, $response->reference, $response->nonReference,
                        $response->referenceScore, $response->nonReferenceScore, $response->time,
                        $response->comment ?? '');
                    array_push($csvData, $results);
                }
            }
        }

        if ($writeData) {
            $this->appendCsv("$resultsPath/bs1116.csv", $csvData);
        }
    }

    private function processLikertMultiStimulus($session, $resultsPath, $length)
    {
        $csvData = [];
        $writeData = false;

        $input = array_merge(["session_test_id", "user_id", "user_email"],
            array_map(fn($i) => $session->participant->name[$i] ?? "field_$i", range(0, $length - 1)),
            ["trial_id", "stimuli_rating", "stimuli", "rating_time"]);
        array_push($csvData, $input);

        foreach ($session->trials as $trial) {
            if ($trial->type == "likert_multi_stimulus") {
                foreach ($trial->responses as $response) {
                    $writeData = true;
                    $results = [$session->testId, $session->userId, $session->userEmail];
                    for ($i = 0; $i < $length; $i++) {
                        array_push($results, $session->participant->response[$i] ?? '');
                    }
                    array_push($results, $trial->id, $response->stimulusRating ?? '',
                        $response->stimulus, $response->time);
                    array_push($csvData, $results);
                }
            }
        }

        if ($writeData) {
            $this->appendCsv("$resultsPath/lms.csv", $csvData);
        }
    }

    private function processLikertSingleStimulus($session, $resultsPath, $length)
    {
        $csvData = [];
        $writeData = false;

        $input = array_merge(["session_test_id", "user_id", "user_email"],
            array_map(fn($i) => $session->participant->name[$i] ?? "field_$i", range(0, $length - 1)),
            ["trial_id"]);

        // Add rating columns
        $firstResponse = $session->trials[0]->responses[0] ?? null;
        $ratingCount = $firstResponse ? count($firstResponse->stimulusRating ?? [1]) : 1;
        if ($ratingCount > 1) {
            for ($i = 0; $i < $ratingCount; $i++) {
                array_push($input, "stimuli_rating" . ($i + 1));
            }
        } else {
            array_push($input, "stimuli_rating");
        }
        array_push($input, "stimuli", "rating_time");
        array_push($csvData, $input);

        foreach ($session->trials as $trial) {
            if ($trial->type == "likert_single_stimulus") {
                foreach ($trial->responses as $response) {
                    $writeData = true;
                    $results = [$session->testId, $session->userId, $session->userEmail];
                    for ($i = 0; $i < $length; $i++) {
                        array_push($results, $session->participant->response[$i] ?? '');
                    }
                    array_push($results, $trial->id);
                    $results = array_merge($results, $response->stimulusRating ?? []);
                    array_push($results, $response->stimulus, $response->time);
                    array_push($csvData, $results);
                }
            }
        }

        if ($writeData) {
            $this->appendCsv("$resultsPath/lss.csv", $csvData);
        }
    }

    private function processSpatialLocalization($session, $resultsPath, $length)
    {
        $csvData = [];
        $writeData = false;

        $input = array_merge(["session_test_id", "user_id", "user_email"],
            array_map(fn($i) => $session->participant->name[$i] ?? "field_$i", range(0, $length - 1)),
            ["trial_id", "name", "stimulus", "position_x", "position_y", "position_z"]);
        array_push($csvData, $input);

        foreach ($session->trials as $trial) {
            if ($trial->type == "localization") {
                foreach ($trial->responses as $response) {
                    $writeData = true;
                    $results = [$session->testId, $session->userId, $session->userEmail];
                    for ($i = 0; $i < $length; $i++) {
                        array_push($results, $session->participant->response[$i] ?? '');
                    }
                    array_push($results, $trial->id, $response->name ?? '', $response->stimulus,
                        $response->position[0] ?? 0, $response->position[1] ?? 0, $response->position[2] ?? 0);
                    array_push($csvData, $results);
                }
            }
        }

        if ($writeData) {
            $this->appendCsv("$resultsPath/spatial_localization.csv", $csvData);
        }
    }

    private function processSpatialAsw($session, $resultsPath, $length)
    {
        $csvData = [];
        $writeData = false;

        $input = array_merge(["session_test_id", "user_id", "user_email"],
            array_map(fn($i) => $session->participant->name[$i] ?? "field_$i", range(0, $length - 1)),
            ["trial_id", "name", "stimulus", "position_outerRight_x", "position_outerRight_y", "position_outerRight_z",
             "position_innerRight_x", "position_innerRight_y", "position_innerRight_z", "position_innerLeft_x",
             "position_innerLeft_y", "position_innerLeft_z", "position_outerLeft_x", "position_outerLeft_y", "position_outerLeft_z"]);
        array_push($csvData, $input);

        foreach ($session->trials as $trial) {
            if ($trial->type == "asw") {
                foreach ($trial->responses as $response) {
                    $writeData = true;
                    $results = [$session->testId, $session->userId, $session->userEmail];
                    for ($i = 0; $i < $length; $i++) {
                        array_push($results, $session->participant->response[$i] ?? '');
                    }
                    array_push($results, $trial->id, $response->name ?? '', $response->stimulus,
                        $response->position_outerRight[0] ?? 0, $response->position_outerRight[1] ?? 0, $response->position_outerRight[2] ?? 0,
                        $response->position_innerRight[0] ?? 0, $response->position_innerRight[1] ?? 0, $response->position_innerRight[2] ?? 0,
                        $response->position_innerLeft[0] ?? 0, $response->position_innerLeft[1] ?? 0, $response->position_innerLeft[2] ?? 0,
                        $response->position_outerLeft[0] ?? 0, $response->position_outerLeft[1] ?? 0, $response->position_outerLeft[2] ?? 0);
                    array_push($csvData, $results);
                }
            }
        }

        if ($writeData) {
            $this->appendCsv("$resultsPath/spatial_asw.csv", $csvData);
        }
    }

    private function processSpatialHwd($session, $resultsPath, $length)
    {
        $csvData = [];
        $writeData = false;

        $input = array_merge(["session_test_id", "user_id", "user_email"],
            array_map(fn($i) => $session->participant->name[$i] ?? "field_$i", range(0, $length - 1)),
            ["trial_id", "name", "stimulus", "position_outerRight_x", "position_outerRight_y", "position_outerRight_z",
             "position_innerRight_x", "position_innerRight_y", "position_innerRight_z", "position_innerLeft_x",
             "position_innerLeft_y", "position_innerLeft_z", "position_outerLeft_x", "position_outerLeft_y",
             "position_outerLeft_z", "height", "depth"]);
        array_push($csvData, $input);

        foreach ($session->trials as $trial) {
            if ($trial->type == "hwd") {
                foreach ($trial->responses as $response) {
                    $writeData = true;
                    $results = [$session->testId, $session->userId, $session->userEmail];
                    for ($i = 0; $i < $length; $i++) {
                        array_push($results, $session->participant->response[$i] ?? '');
                    }
                    array_push($results, $trial->id, $response->name ?? '', $response->stimulus,
                        $response->position_outerRight[0] ?? 0, $response->position_outerRight[1] ?? 0, $response->position_outerRight[2] ?? 0,
                        $response->position_innerRight[0] ?? 0, $response->position_innerRight[1] ?? 0, $response->position_innerRight[2] ?? 0,
                        $response->position_innerLeft[0] ?? 0, $response->position_innerLeft[1] ?? 0, $response->position_innerLeft[2] ?? 0,
                        $response->position_outerLeft[0] ?? 0, $response->position_outerLeft[1] ?? 0, $response->position_outerLeft[2] ?? 0,
                        $response->height ?? 0, $response->depth ?? 0);
                    array_push($csvData, $results);
                }
            }
        }

        if ($writeData) {
            $this->appendCsv("$resultsPath/spatial_hwd.csv", $csvData);
        }
    }

    private function processSpatialLev($session, $resultsPath, $length)
    {
        $csvData = [];
        $writeData = false;

        $input = array_merge(["session_test_id", "user_id", "user_email"],
            array_map(fn($i) => $session->participant->name[$i] ?? "field_$i", range(0, $length - 1)),
            ["trial_id", "name", "stimulus", "position_center_x", "position_center_y", "position_center_z",
             "position_height_x", "position_height_y", "position_height_z", "position_width1_x",
             "position_width1_y", "position_width1_z", "position_width2_x", "position_width2_y", "position_width2_z"]);
        array_push($csvData, $input);

        foreach ($session->trials as $trial) {
            if ($trial->type == "lev") {
                foreach ($trial->responses as $response) {
                    $writeData = true;
                    $results = [$session->testId, $session->userId, $session->userEmail];
                    for ($i = 0; $i < $length; $i++) {
                        array_push($results, $session->participant->response[$i] ?? '');
                    }
                    array_push($results, $trial->id, $response->name ?? '', $response->stimulus,
                        $response->position_center[0] ?? 0, $response->position_center[1] ?? 0, $response->position_center[2] ?? 0,
                        $response->position_height[0] ?? 0, $response->position_height[1] ?? 0, $response->position_height[2] ?? 0,
                        $response->position_width1[0] ?? 0, $response->position_width1[1] ?? 0, $response->position_width1[2] ?? 0,
                        $response->position_width2[0] ?? 0, $response->position_width2[1] ?? 0, $response->position_width2[2] ?? 0);
                    array_push($csvData, $results);
                }
            }
        }

        if ($writeData) {
            $this->appendCsv("$resultsPath/spatial_lev.csv", $csvData);
        }
    }

    private function appendCsv($filePath, $csvData)
    {
        $isFile = Storage::disk('local')->exists($filePath);
        $content = '';

        foreach ($csvData as $index => $row) {
            if ($isFile && $index === 0) {
                // Skip header if file exists
                continue;
            }
            $fp = fopen('php://temp', 'r+');
            fputcsv($fp, $row);
            rewind($fp);
            $content .= stream_get_contents($fp);
            fclose($fp);
        }

        Storage::disk('local')->append($filePath, trim($content));
    }
}
