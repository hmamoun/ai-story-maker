document.addEventListener("DOMContentLoaded", function () {

    const prompt_form = document.getElementById("prompt-form");
    const promptsData = document.getElementById("prompts-data");

    // Capture inline edits
    document.querySelectorAll(".editable").forEach(element => {
        element.addEventListener("input", function () {
            element.dataset.changed = "true";
        });
    });

    // Toggle active checkbox
    document.querySelectorAll(".toggle-active").forEach(checkbox => {
        checkbox.addEventListener("change", function () {
            checkbox.dataset.changed = "true";
        });
    });

    document.addEventListener("click", function (e) {
        if (e.target && e.target.matches(".delete-prompt")) {
            e.target.closest("tr").classList.add("marked-for-deletion");
        }
    });

    // Add new prompt
    const addPromptBtn = document.getElementById("add-prompt");
    if (addPromptBtn) {
        addPromptBtn.addEventListener("click", function () {
            const promptList = document.getElementById("prompt-list");
            const lastRow = promptList.querySelector("tr:last-child");
            if (lastRow) {
                const newRow = lastRow.cloneNode(true);
                // Remove the deleted-prompt class from the new row
                newRow.classList.remove("marked-for-deletion");
                // Clear the changed attribute from the new row
                newRow.querySelectorAll("[data-changed]").forEach(el => {
                    delete el.dataset.changed;
                });
                // Add class unsaved-prompt to the new row, overriding the default color
                newRow.classList.add("new-prompt-row");

                // Reset editable text field to default content
                const textEl = newRow.querySelector("[data-field='text']");
                if (textEl) {
                    textEl.innerText = "New Prompt";
                    delete textEl.dataset.changed;
                }
                // Reset category dropdown to its first option
                const categorySelect = newRow.querySelector("[data-field='category'] select");
                if (categorySelect) {
                    categorySelect.selectedIndex = 0;
                }
                // Reset photos dropdown to its first option
                const photosSelect = newRow.querySelector("[data-field='photos'] select");
                if (photosSelect) {
                    photosSelect.selectedIndex = 0;
                }
                // Uncheck active checkbox and clear changed attribute
                const checkbox = newRow.querySelector("[data-field='active'] .toggle-active, [data-field='active'] input");
                if (checkbox) {
                    checkbox.checked = false;
                    delete checkbox.dataset.changed;
                }
                const promptIdEl = newRow.querySelector("[data-field='prompt_id']");
                if (promptIdEl) {
                    promptIdEl.value = "";
                }

                promptList.appendChild(newRow);
            }
        });
    }

    // Handle form submission
    if (prompt_form) {
        prompt_form.addEventListener("submit", function (event) {
            // Remove the rows with the marked-for-deletion class
            document.querySelectorAll(".marked-for-deletion").forEach(row => {
                row.remove();
            });

            let settings = {
                default_settings: {
                    model: document.getElementById("model").value,
                    system_content: document.getElementById("system_content").value
                },
                prompts: []
            };

            document.querySelectorAll("#prompt-list tr").forEach(row => {
                const textEl = row.querySelector("[data-field='text']");
                if (textEl && textEl.innerText.trim() !== "") {
                    const categorySelect = row.querySelector("[data-field='category'] select");
                    const photosSelect = row.querySelector("[data-field='photos'] select");
                    const activeEl = row.querySelector("[data-field='active']");
                    const prompt_id = row.querySelector("[data-field='prompt_id']");
                    settings.prompts.push({
                        text: textEl.innerText.trim(),
                        category: categorySelect ? categorySelect.value : "",
                        photos: photosSelect ? photosSelect.value : "",
                        active: activeEl && activeEl.checked ? 1 : 0,
                        prompt_id: prompt_id && prompt_id.value ? prompt_id.value : "prompt_" + Date.now() + "_" + Math.floor(Math.random() * 1000)
                    });
                }
            });

            promptsData.value = JSON.stringify(settings).replace(/\\"/g, '"');

            // Allow the form to submit normally
            prompt_form.submit();
        });
    }
});

// check if the button exists before adding the event listener
if (document.getElementById("make-stories-button"))
document.getElementById("make-stories-button").addEventListener("click", function (e) {
    e.preventDefault();

    this.disabled = true;

    const nonce = document.getElementById("generate-story-nonce").value;
    fetch(ajaxurl, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            action: "generate_ai_stories",
            nonce: nonce
        })
    })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => { throw new Error(text) });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const messageDiv = document.getElementById("ai-story-maker-messages");
                messageDiv.className = "notice notice-success visible";
                messageDiv.innerText = "Story generated successfully!";

            } else {
                const messageDiv = document.createElement("div");
                messageDiv.className = "notice notice-error visible";
                messageDiv.innerText = "Error generating stories please check the logs!";
            }
        })
        .catch(error => {
            console.error("Fetch error:", error);
        })
        .finally(() => {
            this.disabled = false;
        });
});


