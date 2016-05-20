<?php
$page_title = "Flordia Summer Camp Kids - History";
include "header.php";
?>
        <span id="content">
            <span id="history">
                <table>
                    <tbody id="history_table_body">
                        <tr>
                            <th>Time</th>
                            <th>IP Address</th>
                            <th>Username</th>
                            <th>Action</th>
                            <th>Target</th>
                        </tr>
                    </tbody>
                </table>
            </span>
        </span>
    </body>
    <script type="text/javascript">
        function pollUpdate(){
            $.get("api/history/poll", function(response){
                if (last_poll !== response){
                    var first = false;
                    if (last_poll === 0){
                        first = true;
                    }
                    $.post("api/history/list", {since: last_poll, first: first}, function(response){
                        eval(response);
                    })
                    last_poll = response;
                }
            })
        }

        last_poll = 0;

        pollUpdate();

        setInterval(pollUpdate, 2500);
    </script>
</html>