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
  name: "",
  email: "",
  password: "",
  password_confirmation: "",
});
const loading = ref(false);
const errors = reactive({});
const localErrors = reactive({});
const successMessage = ref("");

// Validate frontend before submit
const validateForm = () => {
  localErrors.email = "";
  localErrors.password = "";
  localErrors.password_confirmation = "";

  if (!form.email) localErrors.email = "Email is required";
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email))
    localErrors.email = "Please enter a valid email address";

  if (form.password.length > 0 && form.password.length < 6)
    localErrors.password = "Password must be at least 6 characters";

  if (form.password_confirmation && form.password !== form.password_confirmation)
    localErrors.password_confirmation = "Passwords do not match";

  // Clear frontend errors after 3s
  setTimeout(() => {
    Object.keys(localErrors).forEach((key) => (localErrors[key] = ""));
  }, 3000);

  return !localErrors.email && !localErrors.password && !localErrors.password_confirmation;
};

// Submit
const register = async () => {
  Object.keys(errors).forEach((key) => (errors[key] = ""));
  successMessage.value = "";
  loading.value = true;
  const start = Date.now();

  if (!validateForm()) {
    loading.value = false;
    return;
  }

  try {
    const response = await fetch("http://127.0.0.1:8000/api/register", {
      method: "POST",
      headers: { "Content-Type": "application/json", Accept: "application/json" },
      body: JSON.stringify(form),
    });

    const data = await response.json();

    if (!response.ok) {
      if (data.errors) Object.assign(errors, data.errors);
      else console.error("Unexpected response:", data);
      loading.value = false;
      return;
    }

    const elapsed = Date.now() - start;
    const minTime = 1500;

    setTimeout(() => {
      successMessage.value = "Registration successful!";
      Object.keys(form).forEach((key) => (form[key] = ""));
      loading.value = false;
    }, Math.max(0, minTime - elapsed));
  } catch (err) {
    console.error("Network/Server Error:", err);
    localErrors.general = "Unable to register. Please try again later.";
    setTimeout(() => (localErrors.general = ""), 3000);
    loading.value = false;
  }
};
</script>

<template>
  <div class="register-page">
    <!-- Loader overlay -->
    <div v-if="loading" class="loader-overlay">
      <div class="spinner-border text-warning" role="status"></div>
      <p class="mt-2">Creating account...</p>
    </div>

    <form
      @submit.prevent="register"
      class="register-form"
      :class="isMobile ? 'mobile' : ''"
    >
      <h2 class="text-center mb-4">Create Account</h2>

      <!-- Name -->
      <div class="form-group floating">
        <input v-model="form.name" type="text" required placeholder=" " />
        <label>Name</label>
        <small class="text-danger">{{ errors.name?.[0] }}</small>
      </div>

      <!-- Email -->
      <div class="form-group floating">
        <input v-model="form.email" type="email" required placeholder=" " />
        <label>Email</label>
        <small class="text-danger">{{ localErrors.email || errors.email?.[0] }}</small>
      </div>

      <!-- Password -->
      <div class="form-group floating">
        <input v-model="form.password" type="password" required placeholder=" " />
        <label>Password</label>
        <small class="text-danger">{{ localErrors.password || errors.password?.[0] }}</small>
      </div>

      <!-- Confirm Password -->
      <div class="form-group floating">
        <input v-model="form.password_confirmation" type="password" required placeholder=" " />
        <label>Confirm Password</label>
        <small class="text-danger">{{ localErrors.password_confirmation || errors.password_confirmation?.[0] }}</small>
      </div>

      <!-- Submit -->
      <button type="submit" class="btn btn-gradient w-100 mt-3">Sign Up</button>

      <div class="mt-3 text-success text-center" v-if="successMessage">{{ successMessage }}</div>
      <div class="mt-3 text-danger text-center" v-if="localErrors.general">{{ localErrors.general }}</div>
      <p class="mt-3 text-center text-muted">
        Already have an account?
        <router-link
          to="/signin"
          class="text-warning fw-semibold text-decoration-none"
        >
          Log in
        </router-link>
      </p>
    </form>
  </div>
</template>

<style scoped>
.register-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f7d060, #f0b400);
  padding: 20px;
  position: relative;
}

/* Form card */
.register-form {
  background: #fff;
  padding: 40px 30px;
  border-radius: 16px;
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
  width: 400px;
  max-width: 100%;
  transition: transform 0.3s ease;
}
.register-form:hover {
  transform: translateY(-5px);
}
.register-form h2 {
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
.register-form.mobile {
  padding: 30px 20px;
  width: 100%;
}
p a:hover {
  text-decoration: underline;
  color: #f0b400;
}
</style>
