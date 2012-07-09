{if !empty($status)}
<hr width='50%'>
<h2>Provedené změny</h2>
<ul>
{foreach from=$status item='item'}
    <li>{$item.dir}/{$item.file} - {a href='editor/edit' file=$item.file dir=$item.dir}uprav{/a}|{a href='editor/revert' file=$item.file dir=$item.dir}potvrď/zruš{/a}</li>
{/foreach}
{if $status_add}
<h5>Při potvrzení všech změn budou zároveň potvrzeny tyto změny!!!</h5>
<ul>
{foreach from=$status_add item='item'}
    <li>{$item}</li>
{/foreach}
</ul>
{/if}
</ul>
<form method='post' action='{link_to href='editor/commitall'}'>
<input type='submit' value='Potvrď změny'>
</form>
{/if}
