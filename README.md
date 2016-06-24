# TODO

- Add group functoinalilty.
- Add note functionality.
- Add account login extensions.
- Make ajax error output message if available.
- Make documentation much better.
- Add json() methods to Account, Group, and Kid.
- Choose to either accept JSON from client or cleanup custom column preferences.
- Overhall logging to include more detail.
- Maybe use references while modifying array values, not sure.
    - https://stackoverflow.com/questions/3430194/performance-of-for-vs-foreach-in-php
- Add javascript catch if response is not returned in place of response.success
- Replace hide/unhide buttons with checkboxes
- Implement caching.
- Remove dupe comma split to array function in Kid class.
- Make history navigatable by day.
- Improve updateClocks() efficency.
- Pass all relevent data to the client and have them organize it into tables and such.
- Create `full` and `minimal` CSS themes.
- Verify client -> server data validation.
- Clear forms on submission.
- Sanitize server -> client data.
- Sanitize client -> server data.
- Make tool to generate documentation pages.

# Snippets

    header("HTTP/1.0 200 OK");

        echo json_encode([
            "success" => false,
            "message" => "Must be logged in.",
            "redirect" => "http://" . $_SERVER["HTTP_HOST"] . "/login"
        ]);

 * @return string JSON
 *     boolean 'success'
 *     string  'message'
 *     array   'data'    - Something.