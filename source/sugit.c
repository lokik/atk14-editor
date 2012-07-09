#include <stdlib.h>
#include <stdio.h>
#include <unistd.h>

#define MAXARGS 5

static const char* Commands[][MAXARGS]=
{
	{"checkout", "checkout", /*"HEAD" ,*/ "*",},
	{"commitall", "commit", "-a", "-m", "*" },
	{"commit", "commit", "*", "-m", "*"},
        {"push", "push"},
        {"add", "add", "*"},
        {"trycommit", "commit", "-a", "--dry-run", "--short" }
};

#define Command_cnt sizeof(Commands)/sizeof(Commands[0][0])/MAXARGS



int main(int argc, const char* argv[])
{
	int index=-1;
	typedef const char * arg_type;
	arg_type args[MAXARGS+2];
	int r;
	int cmd_arg=2;

	/*printf(
           "         UID           GID  \n"
	         "Real      %d  Real      %d  \n"
	         "Effective %d  Effective %d  \n"
	         "Desired                 %d  \n",
	  
	          getuid (),     getgid (),
	          geteuid(),     getegid(),
	          USE_GROUP
	       );
	*/
	
	int uid;
	uid=geteuid();
	if(setreuid(uid, uid))
			return 105;
	uid=getegid();
	if(setregid(uid, uid))
			return 106;
	
	
  if(chdir(REPOSITORY_DIRECTORY))
			return 104;

	if(argc<=1) return 101;
	for(r=0;r<Command_cnt;r++)
			if(!strcmp(argv[1], Commands[r][0]))
				{
				index=r;
				break;
				}
	if(index==-1)
			return 102;
	
	args[0]="git";	
	
	if(index==0) //checkout
		{
		int mask;
		mask=USE_GROUP>=0?002:0;
		umask(mask);
		}
		
		
	for(r=1;r<MAXARGS;r++)
		{
		const char *cmd=Commands[index][r];
		if(!cmd)
			break;
		if(!strcmp(cmd,"*"))
			{
			if(cmd_arg>=argc) return 103;
			args[r]=argv[cmd_arg++];
			}
		else
			args[r]=cmd;
		}
	args[r]=0;
  return execvp("git", (char**) args);
}
