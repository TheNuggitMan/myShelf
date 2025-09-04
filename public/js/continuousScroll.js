document.addEventListener('DOMContentLoaded', () => {
  
  // Find the grid (and exit if not found)!
  let grid = document.querySelector('.scrollingGrid')
  if (!grid) return
  
  // Get the parent node
  let parent = grid.parentNode
  
  // Clones of the grid
  let clones = [];
  
  // Set the initial scroll position, speed and direction of movement
  let offset = 0,
      parentHeight,
      gridHeight,
      speed = 0.1,
      direction = + speed
  
  // Calculate the height of the parent and the grid and 
  // clone the grid enough times to cover the parent.
  updateHeights()
  
  // update the position on the timer
  window.setInterval(updatePosition, 1)
  
  // update the position when the mouse wheel is used
  parent.addEventListener('wheel', onWheel)
  
  // Update the heights of the window is resized
  window.addEventListener('resize', updateHeights)
  
  function updateHeights(){
    
    parentHeight = parent.getBoundingClientRect().height
    gridHeight   = grid.getBoundingClientRect().height
    
    if (!parentHeight || !gridHeight) return
    
    // work out how many clones are needed
    let required = Math.ceil(parentHeight / gridHeight) + 1
    
    // If there aren't enough clones to cover the parent
    while (required > clones.length){
      
      // Clone the grid and add it to the parent
      let clone = grid.cloneNode(true)
      parent.appendChild(clone)
      
      clones.push(clone)
    
    }
    
    // show the clones at the appropriate offsets
    showPosition()
    
  }
  
  function updatePosition(){
    
    // Change the offset
    offset += direction
    
    // Display the results
    showPosition()
    
  }
  
  function showPosition(){
    
    // Ensure offset is between 0 and the gridHeight
    while (offset < 0) offset += gridHeight
    while (offset >= gridHeight) offset -= gridHeight
    
    grid.style.transform = 'translateY(' + ( - offset) + 'px)'
    
    // Update the relative positions of the clones
    clones.forEach((clone) => {
      //clone.style.transform = 'translateY(' + ((index+1) * gridHeight - offset) + 'px)'
      clone.style.transform = 'translateY(' + ( - offset) + 'px)'
    })
    
  }
  
  function onWheel(e){
    
    e.preventDefault()
    
    // Set the scrolling direction based on the speed
    if (e.deltaY > 50)
      direction = + speed
    else if (e.deltaY < 50)
      direction = - speed
    
    // scroll by a distance set by the wheel
    offset += e.deltaY
    
    // show the new position
    showPosition()
  
  }
  
});