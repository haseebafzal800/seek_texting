<?php
header('Content-type: text/xml');
?>
<Response>
    <Dial callerId="{{ @$from }}">{{ @$forwardTo }}</Dial>
</Response>