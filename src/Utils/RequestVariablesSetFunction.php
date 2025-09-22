<?php
// Verify that the variables are set
if (isset($requestsJsonTemplate) && isset($position) && isset($requestsJsonTemplate[$position])) {
    // Get the request at the given position
    $request = &$requestsJsonTemplate[$position];

    // Check if the field extraVariablesNeededToSet exists
    if (isset($request['extraVariablesNeededToSet'])) {
        foreach ($request['extraVariablesNeededToSet'] as &$extraVariable) {
            $remoteVariableName = $extraVariable['remoteVariableName'];
            $localVariableName = ${$extraVariable['localVariableName']};
            $targetLocation = $extraVariable['targetLocation'];

            // Replacement based on the targetLocation
            switch ($targetLocation) {
                case 'get':
                    $request["url"] = str_replace("{{" . $remoteVariableName . "}}", $localVariableName, $request["url"]);
                    break;
                case 'json':
                    $request["postData"]["text"] = str_replace("{{" . $remoteVariableName . "}}", $localVariableName, $request["postData"]["text"]);
                    break;
                case 'post':
                    $postIndex = array_search($remoteVariableName, array_column($request["postData"]["params"], "name"));
                    if ($postIndex !== false) {
                        $request["postData"]["params"][$postIndex]["value"] = $localVariableName;
                    }
                    break;
                case 'header':
                    $headerIndex = array_search($remoteVariableName, array_column($request["headers"], "name"));
                    if ($postIndex !== false) {
                        $request["headers"][$headerIndex]["value"] = $localVariableName;
                    }
                    break;
                default:
                    break;
            }
        }
    }
}
