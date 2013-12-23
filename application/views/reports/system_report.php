<script src="http://localhost/static/jquery.tablesorter.min.js"></script>

<?php
    echo "<div class=\"page-header\">
            <h1> $system </h1>
         </div>";
?>


<center>
        <?php echo $table; ?> 
</center>

<script type="text/javascript">
    $(document).ready(function() {
        $("#reports_table").tablesorter({ sortList: [[1, 0]] });
    });
</script>
