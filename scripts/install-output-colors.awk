# Presentation only: keep every line and preserve installer exit status via pipefail.
{
 line=$0; lower=tolower(line); color=""
 if (lower ~ /no syntax errors detected|configuracao atual mantida|configuração atual mantida/) color="\033[32m"
 else if (lower ~ /\[erro\]|\[error\]|fatal error|parse error|syntax error|traceback|^erro:|^error:|^falha|permission denied|command not found|arquivo ou diretório inexistente/) color="\033[31m"
 else if (lower ~ /\[aviso\]|\[warning\]/) color="\033[33m"
 else if (lower ~ /\[ok\]|concluida|concluída|finalizado|\[desempenho\]|\[reconcile\]/) color="\033[32m"
 if (color!="") printf "%s%s\033[0m\n",color,line
 else print line
 fflush()
}
