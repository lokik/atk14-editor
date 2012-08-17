<h2>Editace statického obsahu webu - controller {$dir}</h2>

<ul>
{foreach from=$files item=file}
		<li>controller {a href="editor/edit" dir=$dir file=$file}{$file}{/a}
{/foreach}
</ul>
{render partial='editor/status'}
<hr width='50%'>
Seznam všech {a href='editor/index'}adresářů{/a}
