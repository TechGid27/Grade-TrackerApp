import { createRouter, createWebHistory } from 'vue-router'


import Dashboard from '@/view/MainDashboard.vue'
import MainView from '@/view/MainView.vue'
import SubjectsView from '@/view/SubjectsView.vue'
import LoginView from '@/view/Auth/LoginView.vue'
import RegisterView from '@/view/Auth/RegisterView.vue'
import ViewAnalytics from '@/view/ViewAnalytics.vue'
import ViewGrades from '@/view/ViewGrades.vue'
import ManageTaskView from '@/view/ManageTaskView.vue'



const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
      { path: '/', name:"Homepage", component: MainView },
      { path: '/dashboard',name:"Dashboard", component: Dashboard },
      { path: '/subjects',name:"Subjects", component: SubjectsView},
      { path: '/analytics',name:"Analytics", component: ViewAnalytics},
      { path: '/grade', name:"Grades", component: ViewGrades},
       { path: '/task', name:"ManageTask", component: ManageTaskView},
      // Auth
      { path: '/signin', name:"Auth/Signin", component: LoginView },
      { path: '/signup', name:"Auth/Signup", component: RegisterView },

  ],
})










export default router


