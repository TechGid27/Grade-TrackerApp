
import { ref } from "vue";

export function useAssessment(token) {
  const Assessment = ref([]);
  const loading = ref(false);
  const error = ref(null);
  const totalAssessment = ref(0);

  const API_URL = "http://localhost:8000/api/assessments";

  const headers = {
    Authorization: `Bearer ${token}`,
    Accept: "application/json",
    "Content-Type": "application/json",
  };

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
  const getAssessment = async () => {
    const data = await fetchRequest(API_URL);
    if (!data) return;

    Assessment.value = data.Assessment ?? data;
    totalAssessment.value = data.total_Assessment ?? 0;
    console.log("Fetched Assessment:", Assessment.value);
  };

  
  const addAssessment = async (subject) => {
    const data = await fetchRequest(API_URL, {
      method: "POST",
      body: JSON.stringify(subject),
    });
    if (data) await getAssessment();
    return data;
  };
  const updateAssessment = async (subject) => {
    const data = await fetchRequest(`${API_URL}/${subject.id}`, {
      method: "PUT",
      body: JSON.stringify(subject),
    });
    if (data) await getAssessment();
    return data;
  };
  const deleteAssessment = async (id) => {
    const data = await fetchRequest(`${API_URL}/${id}`, {
      method: "DELETE",
    });
    if (data) await getAssessment();
    return !!data;
  };

  return {
    Assessment,
    totalAssessment,
    loading,
    error,
    getAssessment,
    addAssessment,
    updateAssessment,
    deleteAssessment,
  };
}
