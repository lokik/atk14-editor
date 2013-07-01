<?
  class EditorControllerOptions implements ArrayAccess
  {
    public function offsetExists ($offset )
      {
      return array_key_exists($offset, $this->options);
      }
    public function offsetGet ($offset )
      {
        return @$this->options[$offset];
      }

    public function offsetSet ($offset, $value )
      {
      return $this->options[$offset]=$value;
      }

    public function offsetUnset ($offset)
      {
      unset($this->options[$offset]);
      }

    function __construct($config=null)
    {
      $this->options=miniYaml::Load(file_get_contents($config));
    }

    function isEmpty()
    {
      return !(bool) $this->options;
    }

    function valid_directory($controller)
    {
      $dir=$this->valid_controller($controller);
      if(!$dir)
            return false;
      $d=opendir($dir);
      while($action=readdir($d))
        if($this->valid_action($controller, $action))
          return $dir;
      return false;
    }

    function valid_controller($controller)
    {
      if($this->options['allowed-dirname'] && !preg_match($this->options['allowed-dirname'], $controller))
        return false;

      $dir=__DIR__."/../../app/views/$controller";
			#TODO - plugins are often separate projects 
      #if(!is_dir($dir))
      #  $dir=__DIR__."/../../plugins/$controller/views/$controller";

      if(!is_dir($dir))
        return false;

      if(is_string($this->options['allowed']))
        switch($this->options['allowed'])
          {
          case 'all': case '!all': case 'yes': case '*': return $dir;
          }
      if(!is_array($this->options['allowed'])) return false;
      if(isset($this->options['allowed'][$controller]['!exclude'])) return false;
      if(isset($this->options['allowed'][$controller])) return $dir;
      if(isset($this->options['allowed']['!all'])) return $dir;
      return false;
    }

    function valid_action($controller, $action)
    {
      if(!$dir = $this->valid_controller($controller)) 
          return false;
      if($this->options['allowed-filename'] && !preg_match($this->options['allowed-filename'], $action)) 
        return false;

      $file="$dir/$action";
      if(!is_file($file) || !is_writable($file)) 
          return false;
      if(@is_string($this->options['allowed'][$controller]))
        switch($this->options['allowed'][$controller])
          {
          case '!all': case 'all': case 'yes': case '*': return $file;
          }
      $opts=$this->options_for($controller, $action);
      if($opts!==false)
          {
          return (array_key_exists('!exclude', $opts) || array_key_exists('!exclude', $this->options['allowed'][$controller]))?false:$file;
          }
      else
          {
          if(isset($this->options['allowed'][$controller]['!all']))
            return $file;
          if(array_key_exists('!all', $this->options['allowed']) &&
             !array_key_exists($controller, $this->options['allowed'])
            )
            return $file;
          }
      return false;
    }

    /** Not complete implementation, only for testing purposes**/
    function allowEdit($allowed, $controller, $action=null)
    {
        if(!$this->options_for($controller, $action))
            $this->options['allowed'][$controller][$action]=array();
        if($allowed)
            unset($this->options['allowed'][$controller][$action]['!exclude']);
        else
            $this->options['allowed'][$controller][$action]['!exclude']=true;
    }


    function options_for($controller, $action)
    {
      if(!isset($this->options['allowed'][$controller][$action]))
          return false;
      if(is_array($this->options['allowed'][$controller][$action]))
          return $this->options['allowed'][$controller][$action];
      $o=(string) $this->options['allowed'][$controller][$action];
      $args=array();

      if(preg_match("/([^=]*)=(.*)/", $o, $matches))
               $args[$matches[1]]=$matches[2];
      else
               $args[$o]=true;
      return $this->options['allowed'][$controller][$action]=$args;
    }

    function get_view_for($dir, $file)
    {
     $opts=$this->options_for($dir, $file);
     $action=preg_replace('/\.tpl/','', $file);
     //$file=preg_replace('/_(.?)/e',"strtoupper('$1')",$file);
     //$dir=preg_replace('/(?:^|_)(.?)/e',"strtoupper('$1')",$dir);
     if(isset($opts['view']))
         {
         if(preg_match('#([^/?]*)?/?([^/?]*)(\?(.*))#', $opts['view'], $result))
           {
           parse_str($result[4], $args);
           $args['controller']=$result[1]?$result[1]:$dir;
           $args['action']=$result[2]?$result[2]:$action;
           return $args;
           }
         }
     return array('controller' => $dir, 'action' => $action);
    }
  }


class EditorPlugin extends Atk14Plugin
{
  const CommitAll=-1;

  var $referrer=null;
  var $edited=null;

  public function init()
    {
    $this->options=new EditorControllerOptions($this->getConfigFile('yml'));
    }

  function getOptions()
    {
     return $this->options;
    }

  public function link_to($controller, $action, $whatToDo='edit', $add=array())
    {
      $args=array('controller' => 'editor', 'action' => $whatToDo, 'dir' => $controller, 'file' => $action .'.tpl');
      $args=array_merge($args, $add);
      $opts=array();
      if($this->options['devel-server'])
          $opts['with_hostname']=$this['devel-server'];
      return $this->getController()->_link_to($args,$opts);
    }


  public function editLink($controller=null, $referrer=true)
  {
   if(!$controller)
      $controller=$this->getController();
    $add=array();
    if($referrer)
      {
      if($referrer===true)
          $referrer=$controller->request->getRequestUri();
      $add['referrer']=$referrer;
      }
    return self::link_to($controller->controller, $controller->action, 'edit', $add);
  }

  public function commitLink($controller=null)
  {
   if(!$controller)
      $controller=$this->getController();
    return self::link_to($controller->controller, $controller->action , 'revert', array('commit' => true));
  }

  public function revertLink($controller=null)
  {
   if(!$controller)
      $controller=$this->getController();
    return self::link_to($controller->controller, $controller->action , 'revert');
  }


  //given controller has editable template
  public function editable($controller=null)
  {
   if(!$controller)
      $controller=$this->getController();
   if(!$controller)
      return null;

   if(!$this->allowed($controller))
      return false;

   return $this->options->valid_action($controller->controller, $controller->action.'.tpl');
  }

  //can edit at all
  function allowed($controller=null)
    {
    if(!$controller)
      $controller=$this->getController();

    if($this->options->isEmpty())
      return false;

    while($this->options['allowed-ip'])
      {
      $check=$this->options['allowed-ip'];
      if(!is_array($check))
          $check=explode(',',$check);
      $ip=$controller->request->getRemoteAddr();
      foreach($check as $pat)
        {
        $pat=trim($pat);
        if(($pos=strpos($pat, '*'))!==false)
           {
          if($pos===0)
             break 2;
           if(substr($pat,0,$pos)==substr($ip, 0, $pos))
             break 2;
           }
        elseif($pos=strpos($pat, '-'))
          {
          if(substr($pat,0,$pos)<=substr($ip) &&
                substr($pat,$pos+1)>=substr($ip))
            break 2;
          }
        elseif($pat==$ip)
          {
          break 2;
          }
        }
      return false;
      }

    if($this->options['allowed-hostname'] && $this->getController()->_HTTPRequest_serverName!=$this->options['allowed-hostname'])
      return false;

    if($this->options['authentication-method'])
       {
       $method=$this->options['authentication-method'];
       if(method_exists($controller, $method))
         {
         if(!$controller->$method())
          return false;
         }
       elseif($this->options['force-authentication-method'] && strtolower($this->options['force-authentication-method'])!='false')
         return false;
       }
    return true;
    }


  function initReferrer()
      {
      if($this->referrer===null)
        {
        $this->referrer=$this->getSession()->g('__editor:referrer');
        if(!is_array($this->referrer))
            $this->referrer=array();
        }
      }

  function getReferrer($dir, $file, $alt=true)
      {
      $this->initReferrer();
      $ref=@$this->referrer[$dir][$file];
      if(!$ref && $alt)
          $ref=$this->options->get_view_for($dir, $file);
      return $ref;
      }

  function storeReferrer($referrer, $dir, $file, $force=true)
      {
      if($referrer)
        {
          $this->initReferrer();
          if(isset($this->referrers[$dir][$file]) && !$force)
              return;
          $this->referrers[$dir][$file]=$referrer;
          $this->getSession()->s('__editor:referrer',$this->referrers);
        };
      }

  function url($action, $dir, $file)
      {
      return array('controller' => 'edit', 'action' => $action, 'dir' => $dir, 'file' => $file);
      }

  function modified($controller=null, $file=null)
    {
     if(!$controller)
         $controller=$this->getController();
     if(!$file)
         {
         $file=$controller->action.'.tpl';
         $controller=$controller->controller;
         }
     $this->initEdited();
     return (bool) @$this->edited[$controller][$file];
     }

  function initEdited()
      {
      if($this->edited===null)
        {
        $this->edited=$this->getSession()->g('__editor:edited');
        if(!is_array($this->edited))
            $this->edited=array();
        }
      }

  function wasEdited($dir, $file)
      {
      $this->initEdited();
      $this->edited[$dir][$file]=true;
      $this->getSession()->s('__editor:edited', $this->edited);
      }

   function anyChanges()
      {
      $this->initEdited();
      return array_filter($this->edited);
      }

   function commit($fullFile, $dir, $file=null, &$push=null)
      {
      $this->sugit("commit " . escapeshellarg($fullFile) . " 'Změna '".escapeshellarg("$dir/$file"). "' skrze Atk14 editor' ", $ret);
      if(!$ret)
        {
        $this->commited($dir, $file);
                  $this->push($ret, $push);
        }
      return $ret;
      }

  function checkout($fullFile, $dir, $file=null)
      {
      $this->sugit("checkout " . escapeshellarg($fullFile), $ret);
      if(!$ret)
        $this->commited($dir, $file, false);
      return $ret;
      }


  function commited($dir, $file=null)
      {
      if($dir==self::CommitAll)
        {
        $this->getSession()->s('__editor:edited', $this->edited=array());
        }
      else
        {
        $this->initEdited();
        if(!$file)
            die('Bad file in commited');
        unset($this->edited[$dir][$file]);
        $this->getSession()->s('__editor:edited', $this->edited);
        }
      }

  function sugit($cmd, &$ret=0, $as_array=false, $expect=0)
    {
      $cmd=__DIR__.'/bin/sugit ' . $cmd;
      return $this->readCommand($cmd, $ret, $as_array, $expect);
    }

  function   readCommand($cmd, &$ret=0, $as_array=false, $expect=0)
    {
      if($as_array)
          exec($cmd, $result, $ret);
      else
          {
            ob_start();
            passthru($cmd, $ret);
            $result=ob_get_clean();
          }

      if($ret != $expect && $expect!==null)
          $this->getController()->flash->error(sprintf(_('Command %s failed, error code: %s'),$cmd, $ret));
      return $result;
    }

  function fillStatus($controller=null)
    {
     if(!$controller) $controller=$this->getController();
     $controller->tpl_data=array_merge($controller->tpl_data, $this->getStatus());
    }

  function getStatus($asArray=false)
    {
      $result=$this->sugit('trycommit', $ret, true, $expect=1);
      if($ret!=1)
        {
        return array();
        }

      $additional=array();
      $status=array();

      foreach($result as $row)
        {
        if(!trim($row)) continue;
        $code=substr($row, 0,3);
        $file=substr($row, 3);

        switch($code[0])
            {
            case ' ':             //unmodified
            case '?': continue; 	//untracked
            case 'R':
            case 'D':
            case 'U':
            case 'A':
            case 'M':
              if(preg_match('/^app\/views\/(?<dir>[^\/]*)\/(?<file>[^\/]*)/', $file, $matches) &&
                   $this->options->valid_action($matches['dir'], $matches['file'])
                    )
                    {
                      $status[]=array('dir' => $matches['dir'], 'file' => $matches['file']);
                      break;
                    }
              $additional[]=$row;
            default:  continue;
            }
        }

      if($status)
        return array('status' => $status, 'status_add' => $additional);
      if($asArray)
        return array('status' => array(), 'status_add' => $additional);
      return array('status' => false);
    }


  function push(&$ret=0, &$done)
  {
    $done=0;
    if(!$this->options['release'])
        return;
    $options=$this->options['release'];
    if(@$options['disabled'])
        return;

    $ret=0;
    $result=null;

    if(@$options['push'])
        {
        $result.="<br>". $this->sugit('push', $ret);
        $done=!$ret;
        }

    if(!$ret && @$options['pull']['server'])
          {
          $options=@$options['pull'];
          $cmd='ssh ' . $this->options['server'];
          if(@$options['port'])
              $cmd.=' -p '.(int) $this->options['port'];

          switch(@$options['method'])
              {
              default:
              case 'command':
                    $cmd.=' cd ' . escapeshellarg(escapeshellarg($options['dir'])) . '\&\& git pull';
                    break;
              case 'ssh-only':
                    break;
              }
          $result.="<br>".$this->readCommand($cmd, $ret);
          $done=$done || !$ret;
          }
    if($ret)
        return false;
    return $result;
  }

  function fileDiff($fullFile)
      {
      exec('git diff -w ' . escapeshellarg($fullFile), $diff, $ret);
      if($ret)
          $this->getController()->flash->error(sprintf(_('Command git diff failed, error code: %s'), $ret));
      $diff=array_slice($diff, 5);
      $diff=array_map(function($v) {
          $v=htmlspecialchars($v);
          switch(@$v[0])
            {
            case '@': return '<hr>';
            case '+': return "<span style='color: blue'>$v</span>";
            case '-': return "<span style='color: red'>$v</span>";
            case '\\': return null;
            default: return $v;
            }
          },
          $diff);
      return array_filter($diff);
      }

  function commitAll(&$ret=0)
      {
      $result=$this->sugit('commitall "Changes via Atk14 template editor"', $ret);
      if(!$ret)
          $this->commited(self::CommitAll);
      return $result;
      }

   function beforeRender()
     {
     $smarty=$this->getSmarty();
     if(DEVELOPMENT)
         $smarty->clear_compiled_tpl();
     if($this->allowed())
         $this->registerScript(array('/cs/editor/js', 'jquery.simplemodal.1.4.2.min.js'));     
     $smarty->register_prefilter(array($this, 'smartyFilter'));
     $this->useTemplates();
     }

    function getEditableBlocks()
      {
      if(!isset($this->editableBlocks))
        {
        if($this->options['editable-blocks'])
         {
         $e=$this->options['editable-blocks'];
         if(is_string($e))
             $e=explode(',', $e);
         $e=array_map('trim', $e);
         $e[]='editable';
         }
       else
         $e=array('editable');
        $this->editableBlocks=$e;
        }
      return $this->editableBlocks;
      }

   function smartyFilter($source, $smarty)
     {
     $len=strlen($source);
     $offset=strlen($source)+1;
     $file=null;
     $c=0;

     $e=$this->getEditableBlocks();

     $regexp=implode(')|(?:', $e);
     $regexp='/{\s*((?:'.$regexp.'))(?:\s[^}]*)?}(?!={editable(?:\s[^}]*)?})(.*?)({\s*\/\1\s*})/';
     $offset=0;
     $added=0;

     while($off<$len)
       {
       if(!preg_match($regexp,$source,$matches,PREG_OFFSET_CAPTURE, $offset))
           break;
       $offset=$matches[2][1];
       #ugly hack to find out real template name
       if($file===null)
         {
         #smarty hack to get full file name and path for smarty 2.0, smarty 3.0 doesn't need it
         if(method_exists($smarty, '_parse_resource_name')){	 
         	 $params['resource_name']=$smarty->_current_file;
         	 $params['resource_base_path']=$smarty->getTemplateDir();
         
         	 if (!$smarty->_parse_resource_name($params))
         	 	 return '<!--Cannot determine location of template file, cannot make content editable-->' . $source;
         	 $file= $params['resource_name'];
         } else {
           $file = $smarty->_current_file;
         }
         $file=realpath($file);
         $dir=htmlspecialchars(basename(dirname($file)));
         $file=substr(htmlspecialchars(basename($file)),0,-4);
         }
       $add=sprintf("{editable_ tag='%s' file='%s' dir='%s' offset='%06d'}", $matches[1][0], $file, $dir, $offset-$added);
       $source=substr_replace($source, '{/editable_}', $matches[3][1], 0);
       $source=substr_replace($source, $add, $matches[2][1], 0);
       $lenadded=strlen($add)+12;
       $added+=$lenadded;
       $offset=$matches[0][1]+strlen($matches[0][0])+$lenadded;
       }
    return $source;
   }

   public function editPart($file, $tag, $offset, $newcontent)
   {
     $content=file_get_contents($file);
     if(!in_array($tag, $this->getEditableBlocks()))
        return false;
     if(!$file || !preg_match('/\{'.$tag.'( [^}]*)?\}$/', substr($content, 0, $offset)))
        return false;
     $content=substr($content, 0, $offset).
              preg_replace('/^.*?\{\/\s*'.$tag.'\s*\}/',addslashes(trim($newcontent)).'{/'.$tag.'}', substr($content, $offset));
     file_put_contents($file, $content);
     return 1;
   }

   public function getEditPartContent($file, $tag, $offset)
   {
     $content=file_get_contents($file);

     if(!in_array($tag, $this->getEditableBlocks()))
        return false;
     if(!$file || !preg_match('/\{'.$tag.'( [^}]*)?\}$/', substr($content, 0, $offset)))
       return false;
     if(!preg_match('/^(.*?)(?={\/\s*'.$tag.'\s*})/',substr($content, $offset), $matches))
        return false;
     return $matches[1];
   }
}
