{if $plugin.editor->editable()}
<h2 class="green">Editace obsahu</h2>
<div class='jakozeform'>
    <div> <a href='{$plugin.editor->editLink()}'>Uprav obsah stránky</a></div>
    {if $plugin.editor->anyChanges()}
    {/if}
    {if $plugin.editor->modified()}
      <div> <a href='{$plugin.editor->revertLink()}'>Přehled změn</a></div>
      <form method='post' action='{$plugin.editor->commitLink()}'><input name='test' type='submit' value='Potvrď změny'></form>
    {/if}
</div>
{/if}            

