const showProjectsBtn = document.getElementById('show-projects-btn');
const projectsContainer = document.getElementById('projects-container');

const showContactsBtn = document.getElementById('show-contacts-btn');
const contactsContainer = document.getElementById('contacts-container');

showProjectsBtn.addEventListener('click', function(){
    projectsContainer.style.display ? projectsContainer.style.display = 'none' : projectsContainer.style.display = 'block';
});

showContactsBtn.addEventListener('click', function(){
    contactsContainer.style.display ? contactsContainer.style.display = 'none' : contactsContainer.style.display = 'block';
});