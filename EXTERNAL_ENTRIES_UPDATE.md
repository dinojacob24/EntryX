# Terminology Update: External Entries

## ✅ Change Implemented

Updated the terminology from **"Maximum Participants"** to **"Maximum Number of External Entries"** for better clarity and specificity.

## 🎯 Why This Change?

The field specifically controls the maximum number of **external participants** (not internal students), so the new terminology makes this distinction clear:

- ❌ **Old**: "Maximum Participants" (ambiguous)
- ✅ **New**: "Maximum Number of External Entries" (specific and clear)

## 📝 What Was Updated

### 1. **Form Label** (`admin_dashboard.php`)
```html
<!-- Before -->
<label>Maximum Participants</label>

<!-- After -->
<label>Maximum Number of External Entries</label>
```

**Added helper text:**
```html
<small>Maximum external participants allowed for this program</small>
```

### 2. **Table Header** (`admin_dashboard.php`)
```javascript
// Before
<th>Participants</th>

// After
<th>External Entries</th>
```

### 3. **Database Column Comment**
```sql
ALTER TABLE external_programs 
MODIFY COLUMN max_participants INT DEFAULT 500 
COMMENT 'Maximum number of external entries allowed for this program';
```

## 🎨 Visual Changes

### Form Field
```
┌─────────────────────────────────────────────┐
│ Maximum Number of External Entries          │
│ ┌─────────────────────────────────────────┐ │
│ │ 500                                     │ │
│ └─────────────────────────────────────────┘ │
│ Maximum external participants allowed       │
│ for this program                            │
└─────────────────────────────────────────────┘
```

### Table Header
```
┌──────────────┬──────────┬──────────────────┬────────┬─────────┐
│ Program Name │ Duration │ External Entries │ Status │ Actions │
└──────────────┴──────────┴──────────────────┴────────┴─────────┘
```

## 📊 Example Display

When viewing programs in the table:

```
Program Name: AZURE
Duration: 31-01-2026 - 04-02-2026
External Entries: 0 / 500
Status: ACTIVE
```

This clearly shows:
- **0** = Current external registrations
- **500** = Maximum external entries allowed

## 💡 Benefits

1. **Clarity**: Immediately clear this is for external participants only
2. **Specificity**: Distinguishes from internal students
3. **Consistency**: Aligns with "External Programs" terminology
4. **User-Friendly**: Helper text provides additional context

## 🔄 Backward Compatibility

- ✅ Database column name remains `max_participants` (no breaking changes)
- ✅ API still uses `max_participants` field
- ✅ Only UI labels updated for clarity
- ✅ Existing data unaffected

## 📁 Files Modified

1. **`pages/admin_dashboard.php`**
   - Updated form label
   - Added helper text
   - Updated table header

2. **`database/update_external_entries_terminology.sql`**
   - Added database column comment

## 🎉 Summary

The terminology has been updated throughout the interface to clearly indicate that the field controls **external entries** specifically, not all participants. This makes the system more intuitive and prevents confusion between internal students and external program participants.

**Before:** "Maximum Participants: 500"  
**After:** "Maximum Number of External Entries: 500"

The change is **live and ready to use**! 🚀

---

**Note:** This is a UI-only change. The underlying database structure and API remain unchanged for backward compatibility.
