<?php

namespace ExprUsedAsStringInlineHtml;

function doFoo(): void {
?>
<script src="my.js" nonce=123></script>
<?php
}
