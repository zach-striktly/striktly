#!/bin/bash
WRITE=$WRITE || 1
MYSQLUSER=$MYSQLUSER || "mysqluser"
MYSQLPASSWORD=$MYSQLPASS || "mysqlpassword"
MYSQLDB=$MYSQLDB || $MYSQLUSER
CNAME=$CNAME || ""
DEBUG=1
VERBOSE=1
RANDVAL=$RANDOM
BKDIR=$HOME/.backup
if [[ ! -d $BKDIR ]]; then mkdir $BKDIR; fi
WPCLI=0
DT=$(date +'%m-%d-%y-%H-%M-%s')
ACTIONFOUND=0
## Init functions
function file_change
{
 OIFS=$IFS
 IFS=$'\r\n'
 declare -a MYFILES
 TOCHANGE=$(grep -Hosire 'wordpress-[0-9]\{6\}-[0-9]\{6\}\.[0-9|a-Z|.]*'  --color=none "$MYPATH" |grep -v "$CNAME" | sed -e 's/^\(.*\):.*/\1/g' | sort | uniq)
}

function mod_files
{
 if [[ -z $OIFS ]]; then OIFS=$IFS; IFS=$'\r\n'; fi
 for i in ${TOCHANGE[*]}; do
  if [[ $WRITE -eq 1 ]]; then
   echo "Changing file $i"
   sed -i -e "s/wordpress-[0-9|-]\{13\}\./$CNAME\./g" "$i"
  else
   echo "File $i not modified (Write disabled)"
  fi 
 done
 IFS=$OIFS
}

function cleanup
{
 if [ -e "~/bin/my.sed" ]; then rm -rf ~/bin/my.sed; fi
 if [ -e "~/bin/my.query" ]; then rm -rf ~/bin/mysql.query; fi
 IFS=$OIFS
}

function set_debug
{
 if [[ -z "$1" ]]; then WPDEBUG="true"; else WPDEBUG="$1"; fi
 if [[ $WPCLI == 1 && -x "$MYPATH/wp-cli.phar" ]]; then
  OPWD=`pwd`
  cd $MYPATH
  PHAROUT=$($MYPATH/wp-cli.phar config set --raw WP_DEBUG $WPDEBUG)
 else
  echo "WPCLI not installed."
 fi
 cd "$OPWD"
}
 
function mysql_dump
{
 BACKUP=1
 ARCHIVE=1
 DUMPPATH=$BKDIR'/.mysql-'$MYSQLUSER'-'$DT'.sql'
 DUMPPATHBK=$DUMPPATH'.bk'
 mysqldump -u $MYSQLUSER --all-databases -p$MYSQLPASS > $DUMPPATH
 cp "$DUMPPATH" "$DUMPPATHBK"
 if [[ $WRITE == 1 ]]; then
  sed -i -e 's/wordpress-[0-9]\{6\}-[0-9]\{6\}/'$CNAME'/g' $DUMPPATH
  sed -i -f $HOME/bin/my.sed $DUMPPATH
  mysql -u $MYSQLUSER -p$MYSQLPASS $MYSQLUSER < $DUMPPATH
 fi
 if [[ -e $DUMPPATH ]] && [[ -z $ARCHIVE || $ARCHIVE -eq 0 ]]; then rm -rf $DUMPPATH; fi
 if [[ -e $DUMPPATHBK ]] && [[ -z $BACKUP || $BACKUP -eq 0 ]]; then rm -rf $DUMPPATHBK;  fi
}

function create_exports
{
 if [[ $WPCLI == 1 && -x "$MYPATH/wp-cli.phar" ]]; then
  OPWD=`pwd`
  cd $MYPATH
  $MYPATH/wp-cli.phar export --stdout >$BKDIR'/.wp-export.'$MYSQLUSER'-'$DT'.xml'
 else
  echo "WPCLI not installed."
 fi
 cd "$OPWD"
}
if [[ $# -lt 1 ]]; then # || $# -gt 2 ]]; then
 echo "$0: Usage:\r\n#1: $0 <mysql/application userame>\r\n#2: $0 <mysqluser> <mysqlpassword>\r\n" >/dev/stderr
 exit 1
fi
if [[ $# -gt 1 ]]; then
 P2=$2
 ACTIONFOUND=1
 echo "p2: $P2"
 if [[ ${P2:0:7} == "ACTION:" ]]; then
  FUNC=${P2:7}
  echo "fund $FUNC"
  if [[ ! -z $FUNC ]]; then
   case $FUNC in
    mysql)
	mysql_dump
	echo "dumped"
	;;
    files)
 	echo "files changed"
	file_change
	mod_files
	;;
    debug|debug:true)
	echo "Setting debug"
	set_debug
	;;
     debug:false)
	echo "setting debug false"
	set_debug false
	;;
     default|*)
	echo "Invalid action: $FUNC -- Try mysql or files"
	;;
     esac
   fi  
 fi
fi
#mysql_dump
#file_change
#mod_files

  
  
if [[ $# -eq 1 || $ACTIONFOUND -eq 1 ]]; then
 if [[ -z $MYPATH || ! -d "$MYPATH" ]]; then MYPATH="$HOME/applications/$1/public_html"; fi
 if [[ ! -d "$MYPATH" ]]; then
  echo "Unable to find path $MYPATH" >/dev/stderr
  exit 1
 fi

 if [[ ! -e "$MYPATH/wp-config.php" ]]; then
  echo "Unable to find wp-config" >/dev/stderr
  exit 1
 fi
 if [[ -e "$MYPATH/wp-cli.phar" ]]; then
  WPCLI=1
 fi


 MYSQLUSER=$(grep -e '^.*define.*DB_USER.*$' $MYPATH/wp-config.php | sed -e "s/^.*'\(.*\)',.*'\([a-z|A-Z|0-9]*\)');.*$/\2/g" )
 MYSQLPASS=$(grep -e '^.*define.*DB_PASSWORD.*$' $MYPATH/wp-config.php | sed -e "s/^.*'\(.*\)',.*'\([a-z|A-Z|0-9]*\)');.*$/\2/g" )
elif [[ $# -eq 2 || $ACTIONFOUND -eq 0 ]]; then
 MYSQLUSER=$1
 MYSQLPASS=$2
fi 
if [[ ! -z $VERBOSE ]]; then echo "User: $MYSQLUSER Pass: $MYSQLPASS"; fi
# MYPATH=$4 || "/path/to"
 MYPATH="$HOME/applications/$MYSQLUSER/public_html"
 if [[ ! -d $MYPATH ]]; then echo "Path: $MYPATH doesn't exist. Aborting." >/dev/stderr; exit 1; fi
 echo 's/wordpress-\([\d]{,6}\)-\([\d]{,6}\)\./'$CNAME'/g' > $HOME/bin/my.sed
 echo 's/home\/.*\/'$MYSQLUSER'/home\/master\/applications\/'$MYSQLUSER'/g' >> $HOME/bin/my.sed
 echo "select option_value from wp_options where option_name='siteurl' LIMIT 1;" > $HOME/bin/my.query 
 MYQUERY=$(mysql -u$MYSQLUSER -p$MYSQLPASS $MYSQLUSER < ~/bin/my.query)
 if [[ $? -ne 0 ]]; then echo "Unable to connect to mysql." >/dev/stderr;exit 1; fi
 CNAME=$(echo "$MYQUERY" |tail -1|sed -e 's/^ht.*\(wordpress-[0-9]\{6\}-[0-9]\{6\}\).*$/\1/g')
 if [[ -z $VERBOSE ]]; then echo "Found User: $MYSQLUSER\tPass: $MYSQLPASS\tCNAME: $CNAME\tMYPATH: $MYPATH"; fi

 if  [[ -z $DEBUG ]]; then
  MSED=$(cat ~/bin/my.sed)
  echo -e "my.sed: $MSED"
  echo "mysqluser: $MYSQLUSER pass: $MYSQLPASS cname=$CNAME PATH=$MYPATH"
 fi
 if [[ -z $CNAME ]]; then echo "CNAME Is blank" >/dev/stderr; exit 1; fi


# set_debug false
#mysql_dump
#file_change
#mod_files
 cleanup
