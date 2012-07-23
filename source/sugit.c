#include <stdlib.h>
#include <stdio.h>
#include <unistd.h>

#define MAXARGS 7
#define STR_EXPAND(tok) #tok
#define STR(tok) STR_EXPAND(tok)

static const char* Commands[][MAXARGS]=
{
	{"checkout", "git", "checkout", /*"HEAD" ,*/ "*",},
	{"commitall", "git", "commit", "-a", "-m", "*" },
	{"commit", "git", "commit", "*", "-m", "*"},
  {"push", "git", "push"},
  {"ssh-push", "ssh", STR(SERVER), "-i", STR(KEYFILE), "git", "push"},
  {"add", "git", "add", "*"},
  {"trycommit", "git", "commit", "-a", "--dry-run", "--short" }
};

#define Command_cnt sizeof(Commands)/sizeof(Commands[0][0])/MAXARGS



int main(int argc, const char* argv[])
{
	int index=-1;
	typedef const char * arg_type;
	arg_type args[MAXARGS+2];
	int r;
	int cmd_arg=2;
  const char *cmd;
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
	
	
	if(index==0) //checkout
		{
		int mask;
		mask=USE_GROUP>=0?002:0;
		umask(mask);
		}
		
  cmd=Commands[index][1];		
	args[0]=cmd;
	
	for(r=2;r<MAXARGS;r++)
		{
		const char *c=Commands[index][r];
		if(!c)
			break;
		if(!strcmp(c,"*"))
			{
			if(cmd_arg>=argc) return 103;
			args[r-1]=argv[cmd_arg++];
			}
		else
			args[r-1]=c;
		}
	args[r-1]=0;
  //cmd="/bin/echo";
  return execvp(cmd, (char**) args);
}
