{literal}
function editor__edit(id, dir, file, tag, offset)
{ 
    
    $.ajax({
         'url' :'{/literal}{link_to href='editor/editpart'}{literal}',
         'type' : 'get',
         'contentType' :"application/x-www-form-urlencoded",
         'data' :
              {
              'contentquery' : true,
              'tag' : tag,
              'dir' : dir,
              'file' : file,
              'offset' : offset
              },
         'success' : function(content) {
                var pos=$('#' + id).offset();
                $.modal(content, 
                  {
                  position: [pos.top, pos.left ],   
                  onShow: function() {
                    $('#editor__editbutton').click(function(e)
                      {
                      e.preventDefault(); 
                      e.stopPropagation();                     
                      $.ajax({
                             'url' :'{/literal}{link_to href='editor/editpart'}{literal}',
                             'type' : 'post',
                             'contentType' :"application/x-www-form-urlencoded",
                             'data' :
                                  {
                                  'tag' : tag,
                                  'dir' : dir,
                                  'file' : file,
                                  'xhr' : true,
                                  'offset' : offset,
                                  'content' : $('#editor__content').val()
                                  },
                             'success' : function() {
                                window.location.reload();
                                },
                             'error': function(req, desc, err) {
                               {/literal}
                               alert('{t code="err" desc="desc"}Editing failed, error code: '+%1+', description: '+%2{/t});
                               {literal}
                               return false;   
                               }
                           });
                     });
                  }
                });                                  
            },
         'error': function(req, desc, err) {
           {/literal}
           alert('{t code="err" desc="desc"}Load of text, error code: '+%1+', description: '+%2{/t});
           {literal}
           return false;   
         }
      });      
}
{/literal}

{php}
if(class_exists('NDebugger'))
  NDebugger::enable(true);
{/php}
