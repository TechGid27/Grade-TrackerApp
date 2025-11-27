import { ref, computed } from "vue"; // <-- Import computed

// Reactive state for the composable
const subjects = ref([]);
const loading = ref(false);
const error = ref(null);

const calculateQuarterGrade = (assessments, quarterType) => {
    const quarterAssessments = assessments.filter(a => a.type_quarter === quarterType);

    if (quarterAssessments.length === 0) return 0;

    // Ensure we parse the string scores and total_items to numbers
    const totalScore = quarterAssessments.reduce((sum, a) => sum + parseFloat(a.score), 0);
    const totalItems = quarterAssessments.reduce((sum, a) => sum + parseFloat(a.total_items), 0);

    // Prevent division by zero
    if (totalItems === 0) return 0;

    // Calculate percentage grade, rounded to two decimal places
    return Math.round((totalScore / totalItems) * 100 * 100) / 100;
};


export function useSubjects(token) {

  // New computed property to hold subjects with calculated grades
  const subjectsWithCalculatedGrades = computed(() => {
    if (!subjects.value?.length) return [];

    return subjects.value.map(subject => {

        const previous_quarter_grade = calculateQuarterGrade(subject.assessments, 'preliminary');
        const current_quarter_grade = calculateQuarterGrade(subject.assessments, 'midterm');

        return {
            ...subject, // Keep all existing subject properties
            previous_quarter_grade: previous_quarter_grade,
            current_quarter_grade: current_quarter_grade,
        };
    });
  });


  const getSubjects = async () => {
    loading.value = true;
    error.value = null;
    try {
      const res = await fetch("http://localhost:8000/api/subjects", {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      });
      if (!res.ok) throw new Error(await res.text());
      const data = await res.json();

      // Store the raw subjects data including assessments
      subjects.value = data["All subjects"];

      console.log(data);

    } catch (err) {
      console.error("Fetch Error:", err.message);
      error.value = err.message;
      subjects.value = []; // Clear subjects on error
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
      console.log("success fully added");

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

  return {
    subjects: subjectsWithCalculatedGrades, // <-- EXPORT THE COMPUTED GRADES
    loading,
    error,
    getSubjects,
    addSubject,
    updateSubject,
    deleteSubject
  };
}
