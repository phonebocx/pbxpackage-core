set -g status off
unbind C-b
set-option -g prefix C-a
neww -n core -t 1 ./win0.sh
splitw -h -p 60 watch -t -c -n10 ./win1.sh
splitw -v -p 50 watch -t -c -n5 ./win2.sh
selectp -t 0
#neww -n ast ./screen2.sh
#neww -n auto ./screen3.sh
#selectw -t 1
#selectp -t 0
splitw -v -p 20 watch -t -c -n5 ./win3.sh
selectp -t 0

