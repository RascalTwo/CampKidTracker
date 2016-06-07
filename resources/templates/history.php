<span id="content">
    <br>
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
<script type="text/javascript">
    function pollUpdate(){
        $.get("api/history/poll", function(response){
            if (last_poll !== response){
                var append = false;
                if (last_poll === 0){
                    append = true;
                }
                $.post("api/history/list", {since: last_poll}, function(response){
                    response = JSON.parse(response);
                    for (var i = 0; i < response.length; i++){
                        $("#history_table_body > tr").last().after(response[i].html);
                    }
                })
                last_poll = response;
            }
        })
    }

    last_poll = 0;

    pollUpdate();

    setInterval(pollUpdate, 2500);
</script>
