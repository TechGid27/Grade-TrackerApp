import { createRouter, createWebHistory } from 'vue-router'

import Dashboard from '@/view/MainDashboard.vue'
import MainView from '@/view/MainView.vue'
import SubjectsView from '@/view/SubjectsView.vue'
import LoginView from '@/view/Auth/LoginView.vue'
import RegisterView from '@/view/Auth/RegisterView.vue'
import ViewAnalytics from '@/view/ViewAnalytics.vue'
import ViewGrades from '@/view/ViewGrades.vue'
import ManageTaskView from '@/view/ManageTaskView.vue'
import AboutView from '@/view/AboutView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),



  routes: [
    { path: '/', name:"Homepage", component: MainView },
    { path: '/dashboard', name:"Dashboard", component: Dashboard, meta: { requiresAuth: true } },
    { path: '/subjects', name:"Subjects", component: SubjectsView, meta: { requiresAuth: true } },
    { path: '/analytics', name:"Analytics", component: ViewAnalytics, meta: { requiresAuth: true } },
    { path: '/grade', name:"Grades", component: ViewGrades, meta: { requiresAuth: true } },
    { path: '/task', name:"ManageTask", component: ManageTaskView, meta: { requiresAuth: true } },
    { path: '/about', name:"About", component: AboutView },


    { path: '/signin', name:"Auth/Signin", component: LoginView },
    { path: '/signup', name:"Auth/Signup", component: RegisterView },
  ],
   scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition;
    } else if (to.hash) {
      return { el: to.hash, behavior: 'smooth' };
    } else {
      return { top: 0, behavior: 'smooth' }; 
    }
  }
});

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem("token");

  if (to.meta.requiresAuth && !token) {
    next('/signin');
    return;
  }

  if ((to.path === '/signin' || to.path === '/signup') && token) {
    next('/dashboard');
    return;
  }

  next();
});


export default router;
