# Resolving Git Merge Conflicts

When merging branches you may see messages about `CONFLICT` and files marked with
`<<<<<<<`, `=======`, `>>>>>>>`. These markers show the conflicting changes from
both branches.

1. **Open the files** listed in the conflict messages.
2. **Search for the conflict markers** and decide which changes to keep.
3. **Edit the file** so that only the desired content remains and all markers are
   removed.
4. **Add the resolved files** using `git add <file>`.
5. **Continue the merge** with `git commit` (if merging) or `git rebase --continue`
   (if rebasing).
6. **Run your tests** to ensure everything still works before pushing the
   changes.

For more information see `git help merge` and `git help rebase`.
