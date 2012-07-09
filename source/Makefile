REPOSITORY_DIRECTORY=\"$(CURDIR)/../../..\"
CONFIG_FILE=$(shell if [ -f "$REPOSITORY_DIRECTORY/config/editor_plugin.yml" ] ; then echo "$(REPOSITORY_DIRECTORY)/config/editor_plugin.yml" ; else echo "`pwd`/../config/editor_plugin.yml"; fi ) 
GROUP_NAME=$(shell grep '^[[:blank:]]*permission-group[[:blank:]]*' $(CONFIG_FILE) | sed 's/^[^:]*:[[:space:]]*\([A-Za-z0-9_-]*\)[[:space:]]*$$/\1/')
ifeq ($(strip $(GROUP_NAME)),)
	GROUP=-1
else
	GROUP=$(shell sed -n "s/^$(GROUP_NAME):[^:]*:\([0-9]*\):.*/\1/p" /etc/group)
endif



EXE=sugit
BIN=$(EXE:%=../bin/%)

all: $(BIN) 

../bin/%: %
	cp $< $@

../bin/sugit: sugit
	echo $(CONFIG_FILE )
	rm -f ../bin/sugit
	ln -s ../source/sugit ../bin/sugit 
	if [ -n "$(GROUP_NAME)" ] ; then chgrp $(GROUP_NAME) sugit ; chmod o-x sugit ;  fi
	chmod ug+s sugit

sugit: sugit.c $(CONFIG_FILE)
	@if [ "$(GROUP)" = '' ] ; then echo "No such group: '$(GROUP_NAME)'"; exit -1 ; fi 
	gcc -DUSE_GROUP=$(GROUP) -DREPOSITORY_DIRECTORY=$(REPOSITORY_DIRECTORY)  sugit.c -o sugit
	
clean:
	rm -f $(BIN) $(EXE) 

.PHONY: all clean
