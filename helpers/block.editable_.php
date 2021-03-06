<?
function smarty_block_editable_($params, $content, $smarty)
{
  $editor=$smarty->get_template_vars('plugin');
  $editor=$editor['editor'];
  if(!$editor || !$editor->allowed() || !$editor->options->valid_action($params['dir'],$params['file'].'.tpl'))
      return $content;
  $smarty->assign($params);
  $smarty->assign('content', $content);
  return $smarty->fetch('editor/_editable.tpl');
}
