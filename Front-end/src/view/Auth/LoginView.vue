<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from "vue";

// Responsive check
const width = ref(window.innerWidth);
const isMobile = computed(() => width.value <= 768);

const handleResize = () => (width.value = window.innerWidth);
onMounted(() => window.addEventListener("resize", handleResize));
onUnmounted(() => window.removeEventListener("resize", handleResize));

// Form state
const form = reactive({
 email: "",
 password: "",
});

const errors = reactive({});    // backend validation
const localErrors = reactive({}); // frontend validation
const successMessage = ref("");
const ErrorMessage = ref("");
const loading = ref(false);

const validateForm = () => {
 // Reset only the local (frontend) errors before validation
 Object.keys(localErrors).forEach((key) => (localErrors[key] = ""));

 let isValid = true;

 if (!form.email) {
  localErrors.email = "Email is required";
  isValid = false;
 } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
  localErrors.email = "Please enter a valid email address";
  isValid = false;
 }

 if (!form.password) {
  localErrors.password = "Password is required";
  isValid = false;
 } else if (form.password.length < 6) {
  localErrors.password = "Password must be at least 6 characters";
  isValid = false;
 }

 return isValid;
};

const login = async () => {
 loading.value = true;
 const start = Date.now();

 // Reset messages and backend errors
 Object.keys(errors).forEach((key) => (errors[key] = ""));
 successMessage.value = "";
 ErrorMessage.value = "";

 if (!validateForm()) {
  // Reset local errors after a delay if frontend validation failed
  setTimeout(() => {
   Object.keys(localErrors).forEach((key) => (localErrors[key] = ""));
  }, 3000);
  loading.value = false;
  return;
 }

 try {
  const response = await fetch("http://127.0.0.1:8000/api/login", {
   method: "POST",
   headers: { "Content-Type": "application/json", Accept: "application/json" },
   body: JSON.stringify(form),
  });
  const data = await response.json();

  if (!response.ok) {
  
   if (data.message) {
    const msg = data.message.toLowerCase();

    // Specific error logic based on assumed backend response messages
    if (msg.includes("credentials") || msg.includes("unauthorized") || msg.includes("user not found")) {
     ErrorMessage.value = `Login failed. This email is **not registered**. Please register.`;
    } else if (msg.includes("password wrong") || msg.includes("password mismatch")) {
     ErrorMessage.value = "Login failed. The **password is incorrect** for this email.";
    } else {
     ErrorMessage.value = data.message;
    }

    setTimeout(() => (ErrorMessage.value = ""), 5000);
   }
  
   // Assign backend validation errors (e.g., HTTP 422)
   if (data.errors) Object.assign(errors, data.errors);
   else if (typeof data === "object") Object.assign(errors, data);
  
   return;
  }

  // Success Logic
  const elapsed = Date.now() - start;
  const minTime = 1500;

  localStorage.setItem("token", data.token);
  localStorage.setItem("user", JSON.stringify(data.user));

  setTimeout(() => {
   successMessage.value = "Login successful! Redirecting...";
   Object.keys(form).forEach((key) => (form[key] = ""));
   window.location.href = "/dashboard";
  }, Math.max(0, minTime - elapsed));
 } catch (err) {
  console.error("Network/Server Error:", err);
  ErrorMessage.value = "Network error: Could not connect to the server.";
  setTimeout(() => (ErrorMessage.value = ""), 5000);
 } finally {
  loading.value = false;
 }
};
</script>

<template>
 <div class="login-page">
    <div v-if="loading" class="loader-overlay">
   <div class="spinner-border text-warning" role="status"></div>
   <p class="mt-2">Logging in...</p>
  </div>

  <form
   @submit.prevent="login"
   class="login-form"
   :class="isMobile ? 'mobile' : ''"
  >
   <h2 class="text-center mb-4">Welcome Back</h2>

      <div class="form-group floating">
        <input v-model="form.email" type="email" required placeholder=" " />
    <label>Email address</label>
    <small class="text-danger">{{ localErrors.email || errors.email?.[0] }}</small>
   </div>

      <div class="form-group floating">
    <input v-model="form.password" type="password" required placeholder=" " />
    <label>Password</label>
    <small class="text-danger">{{ localErrors.password || errors.password?.[0] }}</small>
   </div>

   <button type="submit" class="btn btn-gradient w-100 mt-3">Login</button>

      <div class="mt-3 text-success fw-semibold" v-if="successMessage">{{ successMessage }}</div>
   <div class="mt-3 text-danger fw-semibold" v-if="ErrorMessage" v-html="ErrorMessage"></div>

   <p class="mt-3 text-center text-muted">
    Don't you have an account?
    <router-link
     to="/signup"
     class="text-warning fw-semibold text-decoration-none"
    >
     Register
    </router-link>
   </p>
  </form>

 </div>
</template>

<style scoped>
/* Page wrapper */
.login-page {
 min-height: 100vh;
 display: flex;
 align-items: center;
 justify-content: center;
 background: linear-gradient(135deg, #f7d060, #f0b400);
 padding: 20px;
 position: relative;
}

/* Form */
.login-form {
 background: #fff;
 padding: 40px 30px;
 border-radius: 16px;
 box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
 width: 400px;
 max-width: 100%;
 position: relative;
 transition: transform 0.3s ease;
}
.login-form:hover {
 transform: translateY(-5px);
}
.login-form h2 {
 font-weight: 700;
 color: #333;
}

/* Floating label */
.form-group.floating {
 position: relative;
 margin-bottom: 25px;
}
.form-group.floating input {
 width: 100%;
 border: 2px solid #ddd;
 border-radius: 12px;
 /* Added space for label logic to work seamlessly */
 padding: 16px 16px 16px 12px;
 font-size: 1rem;
 transition: all 0.3s;
 outline: none;
}
/* Ensure the :not(:placeholder-shown) logic works for floating label */
.form-group.floating input:not(:placeholder-shown) {
 padding-top: 24px;
 padding-bottom: 8px;
}
.form-group.floating input:focus {
 border-color: #f0b400;
 box-shadow: 0 0 0 3px rgba(240, 180, 0, 0.2);
}
.form-group.floating label {
 position: absolute;
 left: 12px;
 top: 50%;
 transform: translateY(-50%);
 color: #aaa;
 transition: all 0.3s;
 pointer-events: none;
}
/* Move label up when focused or has content */
.form-group.floating input:focus + label,
.form-group.floating input:not(:placeholder-shown) + label {
 top: 5px; /* Moved up slightly to work better with padding */
 left: 10px;
 font-size: 0.8rem;
 color: #f0b400;
 background: #fff;
 padding: 0 4px;
}

/* Button */
.btn-gradient {
 background: linear-gradient(90deg, #f7d060, #f0b400);
 border: none;
 color: #fff;
 font-weight: 700;
 padding: 12px;
 border-radius: 12px;
 transition: all 0.3s;
}
.btn-gradient:hover {
 background: linear-gradient(90deg, #f0b400, #f7d060);
 transform: translateY(-2px);
}

/* Loader overlay */
.loader-overlay {
 position: fixed;
 inset: 0;
 z-index: 1000;
 display: flex;
 flex-direction: column;
 justify-content: center;
 align-items: center;
 background: rgba(255, 255, 255, 0.85);
 font-weight: 600;
 color: #333;
 text-align: center;
}
.loader-overlay p {
 margin-top: 12px;
 font-size: 1rem;
}

/* Mobile adjustments */
.login-form.mobile {
 padding: 30px 20px;
 width: 100%;
}
p a:hover {
 text-decoration: underline;
 color: #f0b400;
}
</style>
