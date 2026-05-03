# SIKAP Backend API Documentation

## 🌐 Base URL
- **Local**: `http://127.0.0.1:8000`
- **Production**: `https://sikap-api.onrender.com`

## 🔐 Authentication
- **Type**: Bearer Token (Laravel Sanctum)
- **Header**: `Authorization: Bearer {token}`

## 📝 REGISTRATION FLOW (SECURE MULTI-STAGE)

### **Important Security Notes:**
- **Stage 1**: Registration → Data cached, OTP sent (no user created)
- **Stage 2**: OTP Verification → User created + Token returned (no separate login needed)
- **Stage 3**: ID Upload → Document uploaded, status = 'pending'
- **Stage 4**: Auto-approval → Local testing (manual approval in production)

### **Testing Features (Local Only):**
- **Auto-approval**: Users automatically approved on first login
- **Debug logging**: Detailed logs in `storage/logs/laravel.log`
- **Error handling**: 500 errors return JSON with exception details

---

## 📝 REGISTRATION FLOW

### 1. Initiate Registration
```http
POST /api/v1/auth/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "worker|employer",
  "phone": "09123456789",
  "barangay": "Abuyog",
  "municipality": "Sorsogon City",
  "referrer_contact": "09123456789" // optional
}
```

**Response (201):**
```json
{
  "message": "Registration initiated. Please check your email for OTP."
}
```

### 2. Verify OTP
```http
POST /api/v1/auth/verify-otp
Content-Type: application/json

{
  "email": "john@example.com",
  "otp": "123456"
}
```

**Response (200):**
```json
{
  "message": "Email verified successfully. Please upload your government ID to complete registration.",
  "user_id": 123,
  "token": "2|abc123...",
  "user": {
    "id": 123,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "worker"
  }
}
```

**Note:** Token is returned immediately after OTP verification - no separate login required.

### 3. Upload Government ID (Role-Specific)
```http
POST /api/v1/auth/upload-id
Authorization: Bearer {token}
Content-Type: multipart/form-data

// For Workers (both files required):
id_file: [file] // JPEG/PNG/JPG, max 5MB
selfie_file: [file] // JPEG/PNG/JPG, max 5MB

// For Employers (only ID required):
id_file: [file] // JPEG/PNG/JPG, max 5MB
```

**Response (200):**
```json
{
  "message": "ID and selfie uploaded. Account is pending admin approval."
}
```
or
```json
{
  "message": "ID uploaded. Account is pending admin approval."
}
```

**File Storage:**
- **Government IDs**: `ids/{user_id}_id_{timestamp}.jpg`
- **Selfies**: `selfies/{user_id}_selfie_{timestamp}.jpg`
- **Location**: Supabase Storage bucket `government-ids`
- **Public URLs**: Files accessible via Supabase public URLs

**Validation Rules:**
- **Workers**: Both `id_file` and `selfie_file` required
- **Employers**: Only `id_file` required
- **File types**: JPEG, PNG, JPG
- **Max size**: 5MB per file
- **Error handling**: 422 for validation errors, 500 for server errors

### 4. Login
```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "token": "2|abc123...",
  "token_type": "Bearer",
  "user": {
    "id": 123,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "worker",
    "verification_status": "approved",
    "verification_badge": true
  }
}
```

### 5. Resend OTP
```http
POST /api/v1/auth/resend-otp
Content-Type: application/json

{
  "email": "john@example.com"
}
```

**Response (200):**
```json
{
  "message": "OTP resent."
}
```

---

## 📍 LOCATIONS (Public - No Auth Required)

### Get Municipalities
```http
GET /api/v1/locations/municipalities
```

**Response (200):**
```json
{
  "data": ["Barcelona", "Bulan", "Bulusan", ...],
  "total": 15
}
```

### Get Barangays
```http
GET /api/v1/locations/barangays?municipality=Sorsogon City
```

**Response (200):**
```json
{
  "data": ["Barangay 1", "Barangay 2", ...],
  "municipality": "Sorsogon City",
  "total": 41
}
```

---

## 👤 PROFILE (Authentication Required)

### Get Profile
```http
GET /api/v1/profile
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "id": 123,
  "name": "John Doe",
  "email": "john@example.com",
  "role": "worker",
  "phone": "09123456789",
  "barangay": "Abuyog",
  "municipality": "Sorsogon City",
  "verification_status": "approved",
  "verification_badge": true,
  "document_url": "https://storage.example.com/ids/123_1234567890.jpg",
  "selfie_url": "https://storage.example.com/selfies/123_selfie_1234567890.jpg",
  "worker_profile": {
    "skills": ["Construction", "Painting"],
    "experiences": [...],
    "references": [...]
  }
}
```

### Update Profile
```http
PUT /api/v1/profile
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "John Updated",
  "phone": "09123456789",
  "barangay": "Abuyog",
  "municipality": "Sorsogon City"
}
```

### Add Character Reference (Workers Only)
```http
POST /api/v1/profile/references
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Juan Dela Cruz",
  "phone": "09123456789",
  "relationship": "Previous Employer"
}
```

**Response (201):**
```json
{
  "message": "Reference added successfully.",
  "reference": {
    "id": 1,
    "name": "Juan Dela Cruz",
    "phone": "09123456789",
    "relationship": "Previous Employer",
    "worker_profile_id": 456
  }
}
```

### Remove Character Reference (Workers Only)
```http
DELETE /api/v1/profile/references/{id}
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "message": "Reference removed successfully."
}
```

### Sync Skills (Workers Only)
```http
POST /api/v1/profile/skills
Authorization: Bearer {token}
Content-Type: application/json

{
  "skills": ["Construction", "Painting", "Carpentry"]
}
```

### Add Experience (Workers Only)
```http
POST /api/v1/profile/experiences
Authorization: Bearer {token}
Content-Type: application/json

{
  "job_title": "Construction Worker",
  "company": "ABC Construction",
  "start_date": "2023-01-01",
  "end_date": "2023-12-01",
  "description": "Built residential houses"
}
```

### Remove Experience (Workers Only)
```http
DELETE /api/v1/profile/experiences/{id}
Authorization: Bearer {token}
```

---

## 💼 JOBS (Authentication Required)

### Get All Jobs
```http
GET /api/v1/jobs
Authorization: Bearer {token}
```

### Create Job (Employer Only)
```http
POST /api/v1/jobs
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Construction Worker Needed",
  "description": "Looking for experienced construction worker...",
  "location": "Sorsogon City",
  "barangay": "Abuyog",
  "wage": 500,
  "job_type": "daily",
  "start_date": "2024-01-15",
  "duration_days": 30,
  "skills_required": ["Construction", "Painting"],
  "contact_info": "09123456789"
}
```

### Apply to Job (Worker Only)
```http
POST /api/v1/jobs/{id}/apply
Authorization: Bearer {token}
Content-Type: application/json

{
  "message": "I'm interested in this job...",
  "contact_info": "09123456789"
}
```

---

## 📊 APPLICATIONS (Authentication Required)

### Get My Applications
```http
GET /api/v1/my-applications
Authorization: Bearer {token}
```

### Accept Application (Employer Only)
```http
PATCH /api/v1/applications/{id}/accept
Authorization: Bearer {token}
Content-Type: application/json

{
  "message": "You're hired! Please contact me..."
}
```

### Confirm Hire (Worker Only)
```http
PATCH /api/v1/applications/{id}/confirm
Authorization: Bearer {token}
```

---

## 🚨 ERROR RESPONSES

### Validation Error (422)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."],
    "id_file": ["The id file field must be a file of type: jpeg, png, jpg."],
    "selfie_file": ["The selfie file field must be a file of type: jpeg, png, jpg."]
  }
}
```

### Authentication Error (401)
```json
{
  "message": "Invalid credentials."
}
```

### Authorization Error (403)
```json
{
  "message": "Email not verified."
}
```
```json
{
  "message": "ID document not uploaded. Please complete your registration."
}
```
```json
{
  "message": "Account pending admin approval. Your ID is being reviewed."
}
```
```json
{
  "message": "Only workers can add references."
}
```

### Not Found (404)
```json
{
  "message": "User not found."
}
```
```json
{
  "message": "Reference not found."
}
```

### Server Error (500) - Local Testing Only
```json
{
  "error": "Upload failed",
  "message": "Client error: `PUT https://...` resulted in a `401 Unauthorized` response",
  "file": "C:\\path\\to\\file.php",
  "line": 111
}
```

**Debug Information (Local Only):**
- Check `storage/logs/laravel.log` for detailed error traces
- 500 errors return exception details in local environment
- File upload errors include file info in logs

---

## 🔧 TESTING NOTES

### Registration Flow Testing:
1. **Register** → Check email for 6-digit OTP
2. **Verify OTP** → User created in database
3. **Upload ID** → Ready for admin approval
4. **Login** → Auto-approved in local environment

### Local Testing Features:
- **Auto-approval**: Users auto-approved on first login (local only)
- **Email**: OTPs sent to `noreply@auth.sikap.xyz`
- **Database**: Supabase PostgreSQL

### Production Differences:
- **Manual approval**: Admin must approve users
- **Real emails**: OTPs sent to actual user emails
- **Secure**: No auto-approval

---

## 📱 FRONTEND INTEGRATION TIPS

### 1. Registration Flow:
```javascript
// Step 1: Register
const registerResponse = await fetch('/api/v1/auth/register', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify(userData)
});

// Step 2: Show OTP input modal
// User checks email, enters OTP

// Step 3: Verify OTP (returns token)
const verifyResponse = await fetch('/api/v1/auth/verify-otp', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ email, otp })
});

// Store token and user data immediately
if (verifyResponse?.token) {
  await SecureStore.setItemAsync('auth_token', verifyResponse.token);
  // No separate login needed!
}

// Step 4: Upload ID (Role-Specific)
const formData = new FormData();
formData.append('id_file', idFile);

// Add selfie only for workers
if (verifyResponse.user.role === 'worker') {
  formData.append('selfie_file', selfieFile);
}

const uploadResponse = await fetch('/api/v1/auth/upload-id', {
  method: 'POST',
  headers: { 
    'Authorization': `Bearer ${verifyResponse.token}`
    // Note: Don't set Content-Type with FormData
  },
  body: formData
});

// Step 5: Navigate to app (already authenticated)
// No login needed - user is already authenticated!
navigation.navigate('Home');
```

### 2. Store Token:
```javascript
localStorage.setItem('token', token);
```

### 3. Authenticated Requests:
```javascript
const headers = {
  'Authorization': `Bearer ${localStorage.getItem('token')}`,
  'Content-Type': 'application/json'
};
```

### 4. File Upload Best Practices:
```javascript
// Check file size before upload
const validateFile = (file) => {
  const maxSize = 5 * 1024 * 1024; // 5MB
  const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
  
  if (file.size > maxSize) {
    throw new Error('File size must be less than 5MB');
  }
  
  if (!allowedTypes.includes(file.type)) {
    throw new Error('Only JPEG, PNG, and JPG files are allowed');
  }
};

// Handle file upload errors
try {
  validateFile(file);
  formData.append('id_file', file);
} catch (error) {
  console.error('File validation failed:', error.message);
  // Show error to user
}
```

### 5. Character References (Workers):
```javascript
// Add reference
const addReference = async (referenceData) => {
  const response = await fetch('/api/v1/profile/references', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(referenceData)
  });
  return response.json();
};

// Remove reference
const removeReference = async (referenceId) => {
  const response = await fetch(`/api/v1/profile/references/${referenceId}`, {
    method: 'DELETE',
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  return response.json();
};
```

---

## 🎯 KEY POINTS FOR FRONTEND

1. **Seamless registration**: Register → OTP → Token → ID Upload (no separate login)
2. **Location dropdowns**: Use locations API for barangay/municipality
3. **Token storage**: Store JWT token immediately after OTP verification
4. **Error handling**: Handle 403 errors for incomplete registration
5. **File upload**: Use FormData for ID upload
6. **Role-based features**: Check user role for employer/worker features
7. **Character references**: Workers can add up to 3 references
8. **Role-specific uploads**: Workers need ID + selfie, employers only ID
9. **File validation**: 5MB max, JPEG/PNG/JPG only
10. **Debug support**: Local environment returns detailed error messages

## 🆕 NEW FEATURES ADDED

### ✅ Role-Specific Document Upload
- **Workers**: Government ID + Selfie required
- **Employers**: Government ID only required
- **Storage**: Supabase Storage with organized file paths

### ✅ Character References System
- **Workers only**: Can add/remove character references
- **Fields**: Name, phone, relationship
- **Validation**: All fields required

### ✅ Enhanced Security
- **Multi-stage registration**: No users until OTP verified
- **Seamless authentication**: Token returned after OTP verification
- **Auto-approval testing**: Local environment only
- **Debug logging**: Detailed error tracking

### ✅ File Storage Improvements
- **Supabase integration**: Files stored in cloud storage
- **Public URLs**: Direct access to uploaded files
- **Organized structure**: Separate folders for IDs and selfies

**Your backend is fully ready for frontend integration!** 🚀

The API documentation file (`API_DOCUMENTATION.md`) contains everything you need to integrate your frontend with the complete SIKAP system including secure registration, role-specific uploads, and character references.
