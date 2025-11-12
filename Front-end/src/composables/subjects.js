import { ref } from "vue";

  const subjects = ref([]);
  const loading = ref(false);
  const error = ref(null);


export function useSubjects(token) {


 const getSubjects = async () => {
    loading.value = true;
    error.value = null;
    const totalSubjects = ref(0);
    try {
      const res = await fetch("http://localhost:8000/api/subjects", {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      });
      if (!res.ok) throw new Error(await res.text());
      const data = await res.json();
      subjects.value = data.subjects ?? data;
      totalSubjects.value = data.total_subjects;

    } catch (err) {
      console.error("Fetch Error:", err.message);
      error.value = err.message;
    } finally {
      loading.value = false;
    }
  };

  // POST
  const addSubject = async (subject) => {
    loading.value = true;
    error.value = null;
    try {
      const res = await fetch("http://localhost:8000/api/subjects", {
        method: "POST",
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
          "Content-Type": "application/json",
        },
        body: JSON.stringify(subject),
      });

      const data = await res.json();
      if (!res.ok) throw new Error(data.message || "Failed to add subject");

      await getSubjects(); // refresh list
      return data;
    } catch (err) {
      console.error("Add Error:", err.message);
      error.value = err.message;
    } finally {
      loading.value = false;
    }
  };

  // PUT - update subject
  const updateSubject = async (subject) => {
    loading.value = true;
    error.value = null;
    try {
      const res = await fetch(`http://localhost:8000/api/subjects/${subject.id}`, {
        method: "PUT",
        headers: {
          "Authorization": `Bearer ${token}`,
          "Accept": "application/json",
          "Content-Type": "application/json"
        },
        body: JSON.stringify(subject)
      });

      const data = await res.json();
      if (!res.ok) throw new Error(data.message || "Failed to update subject");

      await getSubjects();
      return data;
    } catch (err) {
      console.error("Update Error:", err.message);
      error.value = err.message;
    } finally {
      loading.value = false;
    }
  };



  // DELETE - remove subject
  const deleteSubject = async (id) => {
    loading.value = true;
    error.value = null;
    try {
      const res = await fetch(`http://localhost:8000/api/subjects/${id}`, {
        method: "DELETE",
        headers: {
          "Authorization": `Bearer ${token}`,
          "Accept": "application/json"
        }
      });

      if (!res.ok) throw new Error(await res.text());

      // refresh list
      await getSubjects();
      return true;
    } catch (err) {
      console.error("Delete Error:", err.message);
      error.value = err.message;
    } finally {
      loading.value = false;
    }
  };

  return { subjects, loading, error, getSubjects, addSubject, updateSubject, deleteSubject };
}
