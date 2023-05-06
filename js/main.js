const showProjectsBtn = document.getElementById('show-projects-btn');
const projectsContainer = document.getElementById('projects-container');

showProjectsBtn.addEventListener('click', () => {
  if (projectsContainer.style.display === 'none') {
    projectsContainer.style.display = 'block'; 
  } else {
    projectsContainer.style.display = 'none'; 
  }
});

const showContactsBtn = document.getElementById('show-contacts-btn');
const contactsContainer = document.getElementById('contacts-container');

showContactsBtn.addEventListener('click', () => {
  if (contactsContainer.style.display === 'none') {
    contactsContainer.style.display = 'block'; 
  } else {
    contactsContainer.style.display = 'none'; 
  }
});



