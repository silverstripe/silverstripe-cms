#!/usr/bin/env bash

REMOTE_TO_SYNC='upstream' # default remote if none passed
QUIET=false # run in quiet mode
DO_PUSH=false # push to the branches upstream as part of the sync
ONLY_LOCAL=false # Only sync local branches (don't pull down from remote)
SKIP_FETCH=false # Skip the fetch step - really only useful if you know your up to date
Q_FLAG='' # the -q flag for CLI commands - changes to `-q` when running in quiet mode

# Our help function
function usage() {
	echo -e "Syntax `basename $0` [-h] [-p] [-q] [-l] [-s] [-r remote-name]
	-h Show this help
	-p Push changes up to the repo
	-q Quiet - minise output
	-l Only sync local branches
	-s Skip fetching the remote (may cause stale commits to be pushed)
	-r [remote-name] Use this remote to sync with, defaults to ${REMOTE_TO_SYNC}"
}

# Heavy lifting function to coordinate the parts
function run() {
	# store the local branch as we need this later to avoid a git error with forcing branch commits
	local current_branch
	current_branch=$(git rev-parse --abbrev-ref HEAD)

	# if the remote exists, we can continue
	if find_remote `git remote`; then

		# Fetch the remote if required
		${SKIP_FETCH} || (${QUIET} || echo "Fetching remote") && git fetch "${REMOTE_TO_SYNC}"

		# populate the global BRANCHES var
		get_branches_to_sync

		# go through all the candidate branches and sync them if needed
		for branch in ${BRANCHES[@]}; do

			# If we want to sync all branches OR check a branch exists locally
			#if [[ ! ${ONLY_LOCAL} ]] || branch_exists_locally "${branch}"; then
				${QUIET} || echo -e "Resetting branch ${branch}"
				# If the branch is currently checkedout we have to use git reset
				if [[ "${current_branch}" == "${branch}" ]]; then
					git reset -q --hard "${REMOTE_TO_SYNC}/${branch}"
				else
					# force the branch name to point to the remote ref
					git branch -f --no-track "${branch}" "${REMOTE_TO_SYNC}/${branch}"
				fi
				# Push up to the default remote if needed
				${DO_PUSH} && git push ${Q_FLAG} -f `git config --get branch."${branch}".remote` "${branch}":"${branch}"
			#fi

		done
		return 0
	else
		echo "Couldn't find remote: ${REMOTE_TO_SYNC}"
		return 1
	fi
}

# Confirm the remote is in a list of passed remotes
function find_remote() {
	local e
	for e in "$@"; do
		[[ "$e" == "$REMOTE_TO_SYNC" ]] && return 0;
	done
	return 1
}

# Get the name of all the branches on the remote
function get_branches_to_sync() {
	${QUIET} || echo "getting branches"
	BRANCHES=$(git branch -r | grep -e "^ *${REMOTE_TO_SYNC}\/" | sed "s/ *${REMOTE_TO_SYNC}\///")
}

# Configm the passed branch name exists locally
function branch_exists_locally() {
	local e
	for e in $(git branch | sed 's/\** *//'); do
		[[ "$e" == "$1" ]] && return 0
	done
	return 1
}

# Handle the CLI params
while getopts "hr:pqls" OPTION; do
	case ${OPTION} in
		r ) REMOTE_TO_SYNC="$OPTARG"
			;;
		p ) DO_PUSH=true
			;;
		q ) QUIET=true
			Q_FLAG='-q'
			;;
		l ) ONLY_LOCAL=true
			;;
		s ) SKIP_FETCH=true
			;;
		h ) usage
			exit 0
			;;
	esac
done

run
exit $?
