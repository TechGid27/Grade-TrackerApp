import { ref } from "vue";

export function useAssessment(token) {
    // State is slightly renamed for clarity (Assessment -> assessments)
    const assessments = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const totalAssessment = ref(0);

    const API_URL = "http://localhost:8000/api/assessments";
    const API_CALC_URL = "http://localhost:8000/api/assessment"; // Base URL for calculation routes

    const headers = {
        Authorization: `Bearer ${token}`,
        Accept: "application/json",
        "Content-Type": "application/json",
    };

    /**
     * Generic asynchronous fetch request handler.
     * @param {string} url - The API endpoint URL.
     * @param {Object} options - Fetch options (method, body, etc.).
     * @returns {Promise<Object|null>} The parsed JSON data or null on failure.
     */
    const fetchRequest = async (url, options = {}) => {
        loading.value = true;
        error.value = null;
        try {
            const res = await fetch(url, { headers, ...options });
            
            const text = await res.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                // If the response is not JSON (e.g., 204 No Content), use status text
                data = { message: res.statusText || 'Success' };
            }

            if (!res.ok) throw new Error(data.message || `Request failed with status ${res.status}`);
            return data;
        } catch (err) {
            console.error("API Error:", err.message);
            error.value = err.message;
            return null;
        } finally {
            loading.value = false;
        }
    };

    // --- CRUD Methods (Slightly adjusted state assignment) ---

    /** Fetches all assessments (READ) */
    const getAssessment = async () => {
        const data = await fetchRequest(API_URL);
        if (!data) return;
        
        // Use consistent state variable name: assessments.value
        assessments.value = data.Assessment || data; 
        totalAssessment.value = data.total_Assessment || (Array.isArray(data.Assessment) ? data.Assessment.length : 0);
        console.log("Fetched Assessments:", assessments.value);
    };

    /** Adds a new assessment (CREATE) */
    const addAssessment = async (assessment) => {
        const data = await fetchRequest(API_URL, {
            method: "POST",
            body: JSON.stringify(assessment),
        });
        // Note: You should generally refresh the list *after* the operation, 
        // but for a large dataset, refreshing the parent subject list (in the component) 
        // might be more efficient than re-fetching *all* assessments here. 
        // Keeping original logic for now:
        if (data) await getAssessment();
        return data;
    };
    
    /** Updates an existing assessment (UPDATE) */
    const updateAssessment = async (assessment) => {
        const data = await fetchRequest(`${API_URL}/${assessment.id}`, {
            method: "PUT",
            body: JSON.stringify(assessment),
        });
        if (data) await getAssessment();
        return data;
    };
    
    /** Deletes an assessment (DELETE) */
    const deleteAssessment = async (id) => {
        const data = await fetchRequest(`${API_URL}/${id}`, {
            method: "DELETE",
        });
        if (data) await getAssessment();
        // Return true only if request was successful (data is not null)
        return !!data; 
    };

    // --- New Calculation Methods (Based on Laravel Routes) ---

    /**
     * Fetches the percentage calculation for a specific assessment type.
     * @param {string} type - Assessment type ('quiz', 'exam', 'assignment', 'project').
     * @param {string|number} quarter - The quarter ID or name.
     * @param {string|number} subjectId - The subject ID.
     * @returns {Promise<Object|null>}
     */
    const getAssessmentPercentage = async (type, quarter, subjectId) => {
        const url = `${API_CALC_URL}/${type}/${quarter}/${subjectId}`;
        return await fetchRequest(url, { method: "GET" });
    };

    /**
     * Fetches the total percentage across all assessment types for a subject in a quarter.
     * @param {string|number} quarter - The quarter ID or name.
     * @param {string|number} subjectId - The subject ID.
     * @returns {Promise<Object|null>}
     */
    const getTotalSubjectGrade = async (quarter, subjectId) => {
        const url = `${API_CALC_URL}/total/${quarter}/${subjectId}`;
        return await fetchRequest(url, { method: "GET" });
    };
    
    /**
     * Fetches overall grades across all subjects for one quarter.
     * @param {string|number} quarter - The quarter ID or name.
     * @returns {Promise<Object|null>}
     */
    const getQuarterGrades = async (quarter) => {
        const url = `${API_CALC_URL}/grade/${quarter}`;
        return await fetchRequest(url, { method: "GET" });
    };

    /**
     * Fetches the final grade across all quarters and all subjects.
     * @returns {Promise<Object|null>}
     */
    const getFinalOverallGrade = async () => {
        const url = `${API_CALC_URL}/final_grade`;
        return await fetchRequest(url, { method: "GET" });
    };

    // --- Return Statement ---

    return {
        assessments, // Renamed from Assessment
        totalAssessment,
        loading,
        error,
        getAssessment,
        addAssessment,
        updateAssessment,
        deleteAssessment,
        // Added new calculation methods
        getAssessmentPercentage,
        getTotalSubjectGrade,
        getQuarterGrades,
        getFinalOverallGrade,
    };
}