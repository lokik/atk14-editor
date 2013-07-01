<h2>Přehled změn šablony {$dir}/{$file}</h2>
<pre style='margin: 1em; overflow: auto; width=95%; position: relative; background: rgb(250,250,250)' >
{!$diff}
</pre>
<form method='post'>
<input type='submit' value='Vrať všechny změny'>
</form>

<form method='post' action='?commit=1'>
<input type='submit' value='Potvrď změny'>
</form>
<div>{a href='editor/edit' file=$file dir=$dir}Edituj soubor{/a}</div>
<div>{a href='editor/directory' dir=$dir}Seznam šablon tohoto kontroléru{/a}</div>
{if $plugin.editor->getReferrer($dir, $file)}
<div>{a url=$plugin.editor->getReferrer($dir, $file)}Zpět{/a}</div>
{/if}


{render partial='editor/status'}
