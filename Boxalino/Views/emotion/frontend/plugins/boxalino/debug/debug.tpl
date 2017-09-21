{block name='bx_debug'}
{if $logs}
<pre class="xdebug-var-dump" dir="ltr">
<b>array</b> <i>(size={count($logs)})</i>
{foreach $logs as $i => $l}
    {$i} <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">{$l}</font> <i>(length={strlen ($l)})</i>
{/foreach}
</pre>
{/if}
{/block}