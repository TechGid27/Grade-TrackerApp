import { ref } from "vue";

const todos = ref([]);
const loading = ref(false);
const error = ref(null);
const totalTodos = ref(0);

export function useTodos(token) {

  const API_URL = "http://localhost:8000/api/todos";

  const headers = {
    Authorization: `Bearer ${token}`,
    Accept: "application/json",
    "Content-Type": "application/json",
  };

  // Helper for fetch requests
  const fetchRequest = async (url, options = {}) => {
    loading.value = true;
    error.value = null;
    try {
      const res = await fetch(url, { headers, ...options });
      const data = await res.json();
      if (!res.ok) throw new Error(data.message || "Request failed");
      return data;
    } catch (err) {
      console.error("API Error:", err.message);
      error.value = err.message;
      return null;
    } finally {
      loading.value = false;
    }
  };

  // GET all todos
  const getTodos = async () => {
    const data = await fetchRequest(API_URL);
    if (!data) return;

    todos.value = data.todos ?? data; // adjust based on API response
    totalTodos.value = data.total_todos ?? todos.value.length;
    console.log("Fetched Todos:", todos.value);
  };

  // POST - add new todo
  const addTodo = async (todo) => {
    const data = await fetchRequest(API_URL, {
      method: "POST",
      body: JSON.stringify(todo),
    });
    if (data) await getTodos();
    return data;
  };

  // PUT - update todo

  const updateTodo = async (todo) => {
    const data = await fetchRequest(`${API_URL}/${todo.id}`, {
      method: "PUT",
      body: JSON.stringify(todo),
    });
    if (data) await getTodos();
    return data;
  };

  // DELETE - remove todo
  const deleteTodo = async (id) => {
    const data = await fetchRequest(`${API_URL}/${id}`, {
      method: "DELETE",
    });
    if (data) await getTodos();
    return !!data;
  };

  return {
    todos,
    totalTodos,
    loading,
    error,
    getTodos,
    addTodo,
    updateTodo,
    deleteTodo,
  };
}
