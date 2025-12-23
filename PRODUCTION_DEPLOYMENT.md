# Production Deployment Guide - Promotions System

## 🚨 CRITICAL: Database Setup Required

Your production database is missing the `promotions` and `admin_users` tables. This is causing the 500 errors.

### Step 1: Run Database Setup SQL

**Connect to your production database and run:**

```sql
-- File: api/database_setup.sql
-- This creates the required tables in your EXISTING database
```

**Tables Created:**
- `promotions` - Stores all promotion/event data
- `admin_users` - Stores admin login credentials

**Default Admin User:**
- Username: `Angel`
- Password: `AngelStones@2025`
- Email: `info@theangelstones.com`

---

## 📦 Files to Upload to Production

Upload these **8 files** to your production server:

### 1. Main Pages
```
index.html                       → Root directory
promotions.html                  → Root directory
```

### 2. API Files (in /api/ folder - lowercase!)
```
api/promotions_api.php           → CRUD operations
api/upload_image.php             → Image upload (no crop)
api/admin_auth.php               → Session management
api/.user.ini                    → PHP upload limits (10MB)
api/database_setup.sql           → Database schema (run this first!)
```

### 3. Root Config
```
.user.ini                        → PHP upload limits (10MB)
```

---

## ✅ Deployment Checklist

### Before Upload:
- [ ] Backup current production files
- [ ] Backup production database

### Database Setup:
- [ ] Connect to production MySQL/MariaDB
- [ ] Run `api/database_setup.sql` file
- [ ] Verify tables created: `promotions`, `admin_users`
- [ ] Test admin login works

### File Upload:
- [ ] Upload `index.html` to root
- [ ] Upload `promotions.html` to root
- [ ] Upload `.user.ini` to root
- [ ] Upload all files to `/api/` folder (lowercase!)
- [ ] Set folder permissions: `chmod 755 api/`
- [ ] Set file permissions: `chmod 644 api/*.php`
- [ ] Create images folder: `mkdir -p images/promotions`
- [ ] Set upload permissions: `chmod 777 images/promotions/`

### Testing:
- [ ] Visit homepage - check for errors in console
- [ ] Check banner loads (if promotions exist)
- [ ] Visit `/promotions.html`
- [ ] Login with: Angel / AngelStones@2025
- [ ] Create test promotion with image upload
- [ ] Verify image appears in banner
- [ ] Verify popup appears on homepage
- [ ] Test "Learn More" button opens in new tab
- [ ] Delete test promotion
- [ ] Verify image file deleted from server

---

## 🔧 Key Changes Made

### API Path Fixed:
- Changed from `/Api/` to `/api/` (lowercase)
- Matches production server folder structure

### CSS Reference Removed:
- Removed `css/promotion-banner.css` reference
- All styles are inline in `index.html`

### Image Upload:
- No cropping - preserves full image
- Resizes to fit within 1200x600
- Converts to WebP automatically
- 10MB upload limit

### Session Management:
- Admin sessions expire on browser close
- Secure password hashing (bcrypt)

---

## 🐛 Troubleshooting

### Error: "Database error occurred"
**Cause:** Promotions table doesn't exist
**Fix:** Run `api/database_setup.sql` in production database

### Error: "404 Not Found" on API calls
**Cause:** Files in wrong folder or wrong case
**Fix:** Ensure files are in `/api/` (lowercase) folder

### Error: "Failed to load CSS"
**Cause:** Reference to non-existent file
**Fix:** Already removed in updated `index.html`

### Error: "File too large"
**Cause:** PHP upload limit too small
**Fix:** Upload `.user.ini` files to set 10MB limit

### Login fails
**Cause:** Admin user not created or wrong password
**Fix:** Run database_setup.sql to create admin user

---

## 📊 Performance

**Optimizations Applied:**
- Inline CSS/JS (fewer HTTP requests)
- Gzip compression ready
- WebP image conversion
- Lazy loading for images
- Session-based popup (once per session)

**Expected Load Time:**
- Homepage: ~1.5s (with images)
- Admin panel: ~800ms
- Image upload: ~2-3s (includes optimization)

---

## 🔐 Security

**Implemented:**
- Bcrypt password hashing
- Session-only cookies (expire on browser close)
- SQL injection prevention (prepared statements)
- XSS prevention (input sanitization)
- File type validation (images only)
- File size limits (10MB max)
- Secure file upload handling

**Recommendations:**
- Change admin password after first login
- Use HTTPS for admin panel
- Regular database backups
- Monitor upload folder size

---

## 📱 Mobile Support

**Current Status:**
- Responsive design implemented
- Touch-friendly controls
- Mobile-optimized images
- Adaptive layouts

**Future Enhancements:**
- Touch gestures for carousel
- Smaller image sizes for mobile
- Progressive Web App features

---

## 🎯 Login Credentials

**Production Admin:**
```
URL: https://theangelstones.com/promotions.html
Username: Angel
Password: AngelStones@2025
```

**IMPORTANT:** Change password after first login!

---

## 📞 Support

If you encounter issues:
1. Check browser console for errors
2. Check server error logs
3. Verify database tables exist
4. Verify file permissions
5. Test API endpoints directly

---

## ✨ Features Delivered

- ✅ Event popup (auto-shows once per session)
- ✅ Banner carousel (events prioritized)
- ✅ Promotions section (Current/Past tabs)
- ✅ Admin panel (create/edit/delete)
- ✅ Image upload (no crop, WebP optimization)
- ✅ Auto-delete images on promotion delete
- ✅ ADA compliance (ESC key, ARIA labels)
- ✅ Session management
- ✅ Mobile responsive

---

**Deployment Date:** December 22, 2025
**Version:** 1.0
**Status:** Ready for Production 🚀
