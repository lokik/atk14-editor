<h2>Editace statického obsahu {$dir}/{$file}</h2>
<form method='post'>
<textarea style='width: 95%;' rows='40' name='content'>{$content|h}</textarea>
<input type='submit' value='Zmeň'>
</form>
<hr width='50%'>
{if $revert}
<div>{a href='editor/revert' file=$file dir=$dir}Přehled změn tohoto souboru{/a}</div>
{/if}
<div>{a href='editor/directory' dir=$dir}Seznam šablon v tomto adresáři{/a}</div>
{if $plugin.editor->getReferrer($dir, $file)}
<div>{a url=$plugin.editor->getReferrer($dir, $file)}Zpět{/a}</div>
{/if}
{render partial='editor/status'}
