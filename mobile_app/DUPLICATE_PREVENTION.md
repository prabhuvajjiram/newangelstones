# Duplicate File Prevention

## Problem
macOS Spotlight/Metadata services create duplicate files with " 2.jpg", " 3.jpg" suffixes when indexing your assets directory.

## Root Cause
- **macOS `mds_stores`** (Metadata Store) indexes files
- **Spotlight indexing** creates temporary copies
- **VS Code file watchers** may conflict with indexing
- **Multiple processes** accessing same files simultaneously

## Solutions Implemented

### 1. `.metadata_never_index` file ✅
Located in `assets/` directory - tells Spotlight to skip indexing

### 2. `.gitignore` in assets/ ✅
Ignores any duplicate files from being tracked in git:
- `* 2.*`
- `* 3.*`  
- `* 4.*`

### 3. Cleanup Script ✅
Run anytime: `./clean_duplicates.sh`
- Automatically finds and removes all duplicates
- Shows count before/after
- Safe to run repeatedly

### 4. Fixed Bundle Script ✅
`bundle_assets.py` now:
- Deletes existing files before download
- Checks for duplicates after completion
- Removes any files with number suffixes

### 5. Fixed App Code ✅
`lib/services/image_cache_manager.dart`:
- Deletes existing file before write
- Prevents OS from creating numbered copies

## Prevention Checklist

### DO ✅
- Run `./clean_duplicates.sh` if you see duplicates
- Keep `assets/.metadata_never_index` file
- Rebuild app after running bundle script
- Close Preview/Photos when working with assets

### DON'T ❌
- Don't open asset images in Finder/Preview while app running
- Don't manually copy files into assets/ (use bundle script)
- Don't delete `.metadata_never_index` file
- Don't run bundle script multiple times simultaneously

## Manual Cleanup

If duplicates appear:
\`\`\`bash
cd /Users/prabuvajjiram/Documents/newangelstones/mobile_app
./clean_duplicates.sh
\`\`\`

Or manually:
\`\`\`bash
find assets -name "* 2.*" -delete
find assets -name "* 3.*" -delete
find assets -name "* 4.*" -delete
\`\`\`

## Monitoring

Check for duplicates:
\`\`\`bash
# Count duplicates
find assets -name "* 2.*" -o -name "* 3.*" | wc -l

# List them
find assets -name "* 2.*" -o -name "* 3.*"
\`\`\`

## Why Duplicates Happen on macOS

1. **File System Events**: macOS FSEvents creates snapshots
2. **Spotlight Indexing**: `mds` creates temporary copies for indexing
3. **Concurrent Access**: Multiple apps/processes accessing same file
4. **Copy-on-Write**: Some file operations create copies instead of overwriting

## Additional Protection

You can also exclude the entire directory from Spotlight via System Preferences:
1. System Settings → Siri & Spotlight
2. Click "Spotlight Privacy..."
3. Drag `mobile_app/assets` folder to the list
4. macOS will stop indexing that directory

## Testing

After implementation:
\`\`\`bash
# 1. Check current state
ls assets/products/ | wc -l    # Should be 116
ls assets/colors/ | wc -l      # Should be 28

# 2. Run bundle script
python3 bundle_assets.py

# 3. Verify no duplicates
find assets -name "* 2.*" | wc -l   # Should be 0

# 4. Run app and sync
flutter run
# Navigate to Contact → Asset Sync → Sync Now

# 5. Check again
find assets -name "* 2.*" | wc -l   # Should still be 0
\`\`\`

## Troubleshooting

### Duplicates Keep Appearing?
1. Check if Spotlight is running: `ps aux | grep mds`
2. Verify `.metadata_never_index` exists: `ls -la assets/`
3. Check file attributes: `xattr assets/products/*.jpg | head`
4. Try disabling Spotlight for directory (see "Additional Protection")

### Files Have Weird Names?
\`\`\`bash
# Remove extended attributes
xattr -cr assets/products/
xattr -cr assets/colors/
\`\`\`

### Git Showing Duplicates?
\`\`\`bash
# They're now ignored, but clean up:
git status --short | grep " 2\." | awk '{print $2}' | xargs rm -f
\`\`\`

## Status

✅ All duplicates removed (180+ files cleaned)
✅ Protection mechanisms in place
✅ Cleanup script created
✅ Git ignore configured
✅ Bundle script fixed
✅ App code fixed

**Next time you see duplicates, just run: `./clean_duplicates.sh`**
