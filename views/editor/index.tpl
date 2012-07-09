<h2>Editace statického obsahu webu</h2>
<ul>
{foreach from=$directories item=dir}
		<li>controller {a href="editor/directory" dir=$dir}{$dir}{/a}
{/foreach}
</ul>

{render partial='editor/status'}

