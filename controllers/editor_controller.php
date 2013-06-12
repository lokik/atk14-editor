<?
  class EditorController extends ApplicationController
  {
    function _before_filter()
      {
        chdir(__DIR__.'/../../');
        $this->editor=$this->plugins['editor'];
        $this->options=$this->editor->getOptions();
        if(!$this->editor->allowed())
            return $this->error404();

        //$this->tpl_data['i']=$this;
      }

    function js()
      {
       $this->content_type='text/javascript';
       $this->render_layout=false;
      }

    function index()
    {
      $this->tpl_data['directories']=array_filter(scandir(__DIR__ . '/../../../app/views'),
                                      array($this->options, 'valid_directory'));
      $this->editor->fillStatus();
    }

    function directory()
    {
      $dir=$this->params->getString('dir');
      $fullDir=$this->options->valid_controller($dir);
      if(!$fullDir)
          return $this->error404();

      $this->tpl_data['dir']=$dir;
      $opt=$this->options;
      $this->tpl_data['files']=array_filter(scandir($fullDir),
                                      function($v) use ($dir, $opt) {
                                                      return $opt->valid_action($dir, $v);
                                                   } );
      $this->editor->fillStatus();
    }

    function editpart()
    {
      $dir=$this->params->getString('dir');
      $file=$this->params->getString('file');
      if(substr($file,-4)!='.tpl')
        $file.='.tpl';
      $fullFile=$this->options->valid_action($dir, $file);
      if(!$fullFile)
          return $this->error404();
      if($this->params['referrer'])
          $this->editor->storeReferrer($this->params['referrer'], $dir, $file);
      if($this->request->post())
        {
        if($this->request->post() && $content=$this->params['content'])
          {
          if($this->editor->editPart($fullFile, $this->params['tag'], $this->params['offset'], $this->params['content']))
            {
            $this->editor->wasEdited($dir, $file);
            if($this->request->xhr())
              $this->render_template=false;
            else
              {
              $referrer=$this->editor->getReferrer($dir, $file, 'revert');
              $this->_redirect_to($referrer);
              }
            return;
            }
          else
            return $this->response->setStatusCode(406);
          }
        }
      $this->tpl_data['content']=$result=$this->editor->getEditPartContent($fullFile, $this->params['tag'], $this->params['offset']);
      $this->tpl_data['xhr']=$this->request->xhr() || $this->params['xhr'];
      $this->tpl_data['dir']=$dir;
      $this->tpl_data['file']=$file;
      $this->tpl_data['offset']=$this->params['offset'];
      $this->tpl_data['tag']=$this->params['tag'];


      if($result===false)
            {
            return $this->response->setStatusCode(406);
            }
      if(class_exists('NDebugger'))
             NDebugger::enable(true);
    }

    function revert()
    {
      $dir=$this->params->getString('dir');
      $file=$this->params->getString('file');
      $fullFile=$this->options->valid_action($dir, $file);

      if(!$fullFile)
          return $this->error404();

      if($this->request->post())
          {
          if($this->params['commit'])
            {
            $referrer=$this->editor->getReferrer($dir, $file, 'edit');
            $ret=$this->editor->commit($fullFile, $dir, $file, $push);
            if(!$ret)
              {
              $notice=_('Změny byly potvrzeny');
              if($push)
                $notice.=_(' a odeslány na server');
              $notice.='.';
              }
            }
          else
            {
            $ret=$this->editor->checkout($fullFile, $dir, $file);
            if(!$ret)
              $notice=_('Všechny změny šablony byly zrušeny.');
            $referrer=array('action' => 'edit', 'dir' => $dir, 'file' => $file);
            }
          if($ret==0)
            {
              $this->flash->notice($notice);
              $this->_redirect_to($referrer);
              return;
            }
          }

      //expand();
      $diff=$this->editor->fileDiff($fullFile);
      $this->tpl_data['diff']=implode("\n",$diff);
      $this->tpl_data['dir']=$dir;
      $this->tpl_data['file']=$file;
//			$this->tpl_data['content']=$content;
      $this->editor->fillStatus();
    }

    function edit()
    {
      $dir=$this->params->getString('dir');
      $file=$this->params->getString('file');

      $fullFile=$this->options->valid_action($dir, $file);
      if(!$fullFile)
          return $this->error404();
      if($this->params['referrer'])
        $this->editor->storeReferrer($this->params['referrer'], $dir, $file);

      $fileEscape=escapeshellarg($fullFile);
      $content=file_get_contents($fullFile);
      if($this->request->post() && $newcontent=trim($this->params['content']))
          {
          if($content==$newcontent)
                $this->flash->notice('Nebyly provedeny žádné změny.');
          else
                {
                file_put_contents($fullFile, $newcontent, LOCK_EX);
                $this->editor->wasEdited($dir, $file);
                $referrer=$this->editor->getReferrer($dir, $file, 'revert');
                $this->_redirect_to($referrer);
                $this->flash->notice('Šablona změněna');
                }
          }

      $this->tpl_data['revert']=exec('git status -s '.$fileEscape);
      $this->tpl_data['dir']=$dir;
      $this->tpl_data['file']=$file;
      $this->tpl_data['content']=$content;
      $this->editor->fillStatus();
    }

    function commitall()
    {
      if($this->request->post())
        {
        $result=$this->editor->commitAll($ret);
        if(!$ret)
          {
          $result.=$this->editor->push($ret);
          }
        if(!$ret)
           {
           $this->tpl_data['result']=$result;
           $this->flash->notice('Změny uloženy');
           }
        }
      if(!$this->editor->fillStatus())
          $this->_redirect_to(array('controller' => 'editor', 'action' => 'index'));
    }
 }
?>
