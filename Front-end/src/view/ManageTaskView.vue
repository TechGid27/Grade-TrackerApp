<script setup>
import { onMounted, computed, ref } from 'vue';
import NavigationChild from './components/NavigationChild.vue';
import SubNavigation from './components/SubNavigation.vue';
import FilterComponent from './components/FilterComponent.vue';
import AddUpdateComponent from './components/AddUpdateComponent.vue';
import { useTodos } from '@/composables/task';

const token = localStorage.getItem("token");
const subjectModal = ref(null);

// composables
const { todos, getTodos, updateTodo, deleteTodo, addTodo } = useTodos(token);

// filters
const currentFilter = ref('All');

// fetch on load
onMounted(getTodos);

// delete task
const handleDelete = async (id) => {
  if (!confirm("Are you sure you want to delete this task?")) return;
  await deleteTodo(id);
};

// toggle completion
const toggleCompleted = async (todo) => {
  todo.completed = !todo.completed;
  try {
    await updateTodo({ ...todo });
  } catch (err) {
    todo.completed = !todo.completed;
    console.error("Failed to update todo:", err);
  }
};

// add task
const handleAdd = async (taskData) => {
  await addTodo(taskData);
  await getTodos();
};

// computed filters
const filteredTodos = computed(() => {
  const now = new Date();
  const today = now.toISOString().split('T')[0];

  switch (currentFilter.value) {
    case 'Pending':
      return todos.value.filter(t => !t.completed);
    case 'Completed':
      return todos.value.filter(t => t.completed);
    case 'Due today':
      return todos.value.filter(t => t.due_date === today);
    case 'Over Due':
      return todos.value.filter(
        t => t.due_date && new Date(t.due_date) < now && !t.completed
      );
    default:
      return todos.value;
  }
});

// counts
const todoCounts = computed(() => {
  const now = new Date();
  const today = now.toISOString().split('T')[0];
  return {
    All: todos.value.length,
    Pending: todos.value.filter(t => !t.completed).length,
    Completed: todos.value.filter(t => t.completed).length,
    'Due today': todos.value.filter(t => t.due_date === today).length,
    'Over Due': todos.value.filter(t => t.due_date && new Date(t.due_date) < now && !t.completed).length,
  };
});

// format date
const formatDate = (dateStr) => {
  if (!dateStr) return '-';
  const dateObj = new Date(dateStr);
  return dateObj.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};
</script>

<template>
  <NavigationChild />
  <SubNavigation :currentFilter="currentFilter" @update:currentFilter="currentFilter = $event" />
  <FilterComponent />

  <!-- Filter Buttons -->
  <div class="container mt-4">
    <ul class="row row-cols-2 row-cols-md-5 list-unstyled text-center g-2">
      <li v-for="filter in ['All','Pending','Due today','Over Due','Completed']" :key="filter">
        <button
          class="custom-btn d-flex justify-content-between align-items-center w-100 py-2 px-3"
          :class="{ active: currentFilter === filter }"
          @click="currentFilter = filter"
        >
          <span>{{ filter }}</span>
          <span class="badge border border-primary text-dark rounded-pill">
            {{ todoCounts[filter] }}
          </span>
        </button>
      </li>
    </ul>
  </div>

  <!-- Task List -->
  <div class="container mt-4 pb-5">
    <transition-group name="fade" tag="div">
      <div
        v-for="todo in filteredTodos"
        :key="todo.id"
        class="task-card shadow-sm p-4 mb-3 rounded"
        :class="{
          'completed': todo.completed,
          'overdue': new Date(todo.due_date) < new Date() && !todo.completed,
          'due-today': todo.due_date === new Date().toISOString().split('T')[0],
        }"
      >
        <div class="row align-items-center">
          <div class="col-auto">
            <input
              type="checkbox"
              class="form-check-input"
              :checked="todo.completed"
              @change="toggleCompleted(todo)"
            />
          </div>
          <div class="col">
            <h5 class="mb-1 fw-semibold">{{ todo.title }}</h5>
            <p class="text-muted small mb-0">{{ todo.description }}</p>
          </div>
          <div class="col-auto d-flex gap-2">
            <button class="btn btn-sm text-primary ri-edit-circle-line fs-5" @click="subjectModal.openForEdit('todos', todo)"></button>
            <button class="btn btn-sm text-danger ri-delete-bin-line fs-5" @click="handleDelete(todo.id)"></button>
          </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3">
          <span
            class="badge text-capitalize fw-semibold py-2 px-3"
            :class="todo.completed ? 'bg-secondary' : todo.priority === 'High' ? 'bg-danger' : todo.priority === 'Medium' ? 'bg-warning text-dark' : 'bg-success'"
          >
            {{ todo.priority }}
          </span>
          <span class="badge bg-white border text-dark py-2 px-3 fw-semibold">
            <span :class="todo.subject?.color || ''" class="border rounded-circle px-2">&nbsp;</span>
            {{ todo.subject?.name || 'No Subject' }}
          </span>
          <span class="fw-semibold text-danger">
            {{ formatDate(todo.due_date) }}
          </span>
        </div>
      </div>
    </transition-group>

    <!-- Empty State -->
    <div v-if="filteredTodos.length === 0" class="text-center text-muted mt-5">
      <i class="ri-inbox-2-line fs-1"></i>
      <p class="mt-2">No tasks found for this filter.</p>
    </div>
  </div>

  <AddUpdateComponent ref="subjectModal" @add="handleAdd" />
</template>

<style scoped>
/* transition */
.fade-enter-active, .fade-leave-active {
  transition: all 0.4s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* filter buttons */
.custom-btn {
  border: none;
  border-radius: 12px;
  background: white;
  box-shadow: 0 2px 4px rgba(0,0,0,0.15);
  transition: all 0.2s ease;
}
.custom-btn:hover {
  background: #f2f6ff;
  transform: translateY(-1px);
}
.custom-btn.active {
  background: #0d6efd;
  color: white;
}

/* task card */
.task-card {
  background: #f8f9fa;
  transition: all 0.3s ease;
}
.task-card:hover {
  transform: translateY(-3px);
}
.task-card.completed {
  opacity: 0.6;
  text-decoration: line-through;
}
.task-card.overdue {
  border-left: 5px solid #dc3545;
}
.task-card.due-today {
  border-left: 5px solid #ffc107;
}
</style>
