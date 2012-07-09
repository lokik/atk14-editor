<h2>Editace části {$dir}/{$file}</h2>
<form method='post' action='{link_to href='editor/revert' file=$file dir=$dir tag=$tag offset=$offset}'>
<textarea style='width: 95%;' rows='10' id='editor__content' name='content'>{$content|h}</textarea>
{if $xhr}
<button type='reset' onclick='$.modal.close()'>Zruš</button>
<button id='editor__editbutton'>Změň</button>
{else}
<input type='submit' value='Zmeň'>
{/if}
</form>


{if $xhr}

{else}
<hr width='50%'>
{if $revert}
<div>{a href='editor/revert' file=$file dir=$dir}Přehled změn tohoto souboru{/a}</div>
{/if}
<div>{a href='editor/directory' dir=$dir}Seznam šablon tohoto kontroléru{/a}</div>
{if $plugin.editor->getReferrer($dir, $file)}
<div>{a url=$plugin.editor->getReferrer($dir, $file)}Zpět{/a}</div>
{/if}
{render partial='editor/status'}
{/if}
