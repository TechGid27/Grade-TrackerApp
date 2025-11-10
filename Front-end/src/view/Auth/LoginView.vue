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

const errors = reactive({});       // backend validation
const localErrors = reactive({});  // frontend validation
const successMessage = ref("");
const ErrorMessage = ref("");
const loading = ref(false);

const validateForm = () => {
  localErrors.email = "";
  localErrors.password = "";

  if (!form.email) localErrors.email = "Email is required";
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email))
    localErrors.email = "Please enter a valid email address";

  if (!form.password) localErrors.password = "Password is required";
  else if (form.password.length < 6)
    localErrors.password = "Password must be at least 6 characters";

  // clear frontend errors after 3s
  setTimeout(() => {
    Object.keys(localErrors).forEach((key) => (localErrors[key] = ""));
  }, 3000);

  return !localErrors.email && !localErrors.password;
};

const login = async () => {
  loading.value = true;
  const start = Date.now();
  Object.keys(errors).forEach((key) => (errors[key] = ""));
  successMessage.value = "";
  ErrorMessage.value = "";

  if (!validateForm()) {
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
        ErrorMessage.value = data.message;
        setTimeout(() => (ErrorMessage.value = ""), 3000);
      }
      if (data.errors) Object.assign(errors, data.errors);
      else if (typeof data === "object") Object.assign(errors, data);
      return;
    }

    const elapsed = Date.now() - start;
    const minTime = 1500;

    localStorage.setItem("token", data.token);
    localStorage.setItem("user", JSON.stringify(data.user));

    setTimeout(() => {
      successMessage.value = "Login successful!";
      Object.keys(form).forEach((key) => (form[key] = ""));
      window.location.href = "/dashboard";
    }, Math.max(0, minTime - elapsed));
  } catch (err) {
    console.error("Network/Server Error:", err);
    ErrorMessage.value = "Something went wrong. Please try again.";
    setTimeout(() => (ErrorMessage.value = ""), 3000);
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="login-page">
    <!-- Fullscreen Loader -->
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

      <!-- Email -->
      <div class="form-group floating">
        <input v-model="form.email" type="email" required />
        <label>Email address</label>
        <small class="text-danger">{{ localErrors.email || errors.email?.[0] }}</small>
      </div>

      <!-- Password -->
      <div class="form-group floating">
        <input v-model="form.password" type="password" required />
        <label>Password</label>
        <small class="text-danger">{{ localErrors.password || errors.password?.[0] }}</small>
      </div>

      <button type="submit" class="btn btn-gradient w-100 mt-3">Login</button>

      <div class="mt-3 text-success" v-if="successMessage">{{ successMessage }}</div>
      <div class="mt-3 text-danger" v-if="ErrorMessage">{{ ErrorMessage }}</div>
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
  padding: 16px 16px 16px 12px;
  font-size: 1rem;
  transition: all 0.3s;
  outline: none;
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
.form-group.floating input:focus + label,
.form-group.floating input:not(:placeholder-shown) + label {
  top: -10px;
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
