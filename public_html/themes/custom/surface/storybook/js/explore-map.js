document.addEventListener("DOMContentLoaded", function onDomContentLoaded() {
  const svgMap = document.getElementById("svgMap");

  const rect = svgMap.getBoundingClientRect();
  const svgOffset = {
    top: rect.top + window.pageYOffset,
    left: rect.left + window.pageXOffset,
  };

  const oceans = svgMap.querySelectorAll(".ocean path");

  const theOceans = [
    "Arctic_Ocean",
    "Atlantic_Ocean",
    "Indian_Ocean",
    "Pacific_Ocean",
    "Southern_Ocean",
    "North_Atlantic",
    "Tropical_Atlantic",
    "South_Atlantic",
    "North_Pacific",
    "Tropical_Pacific",
    "South_Pacific",
  ];

  const oceanPage = typeof window.oceanPage !== "undefined" ? window.oceanPage : undefined;

  const fontSize = document.querySelector(".front") ? 22 : 17;

  // Create labels for each ocean
  theOceans.forEach((ocean) => {
    const title = ocean.replace("_", " ");
    const labelBg = document.createElementNS("http://www.w3.org/2000/svg", "rect");
    labelBg.setAttribute("width", "200");
    labelBg.setAttribute("height", "42");
    labelBg.setAttribute("rx", "10");
    labelBg.setAttribute("ry", "10");
    labelBg.setAttribute("fill", "#484540");
    labelBg.setAttribute("opacity", "0.9");
    labelBg.classList.add("label_bg");

    const labelText = document.createElementNS("http://www.w3.org/2000/svg", "text");
    labelText.textContent = title;
    labelText.setAttribute("x", "15");
    labelText.setAttribute("y", "27");
    labelText.setAttribute("fill", "#ffffff");
    labelText.setAttribute("font-size", fontSize);
    labelText.setAttribute("font-weight", "bold");
    labelText.setAttribute("text-anchor", "left");
    labelText.classList.add("ocean_label");

    const labelGroup = document.createElementNS("http://www.w3.org/2000/svg", "g");
    labelGroup.classList.add(`g-${ocean}`);
    labelGroup.appendChild(labelBg);
    labelGroup.appendChild(labelText);

    svgMap.appendChild(labelGroup);
    labelGroup.style.display = "none";
  });

  if (oceanPage) {
    const activeOcean = document.getElementById(oceanPage[0]);
    if (activeOcean) {
      activeOcean.classList.add("active");
    }
  }

  function hoverover(event, element, offsetData) {
    const className = element.getAttribute("class");
    const selected = svgMap.querySelectorAll(`.g-${className}`);
    const bbox = element.getBBox();
    const theTop = bbox.y;
    const right = bbox.x + bbox.width;

    selected.forEach((group) => {
      const label = group.querySelector(".ocean_label");
      const labelBg = group.querySelector(".label_bg");

      const textWidth = label.getBBox().width;
      labelBg.setAttribute("width", textWidth + 30);

      let theX = 0;
      let theY = 0;

      if (className !== "Southern_Ocean" && className !== "Arctic_Ocean") {
        if (className.includes("Atlantic")) {
          if (className === "North_Atlantic") {
            theX = right - 60;
            theY = theTop + 75;
          } else if (className === "Tropical_Atlantic") {
            theX = right - 60;
            theY = theTop + 35;
          } else {
            theX = right + 10;
            theY = theTop + 65;
          }
        } else if (className.includes("Pacific")) {
          if (className === "North_Pacific") {
            theX = right - 25;
            theY = theTop + 75;
          } else if (className === "Tropical_Pacific") {
            theX = right + 10;
            theY = theTop + 50;
          } else {
            theX = right;
            theY = theTop + 50;
          }
        }
      } else {
        theX = event.clientX - offsetData.left >= 450 ? 450 - textWidth - 10 : 450 + 40;
        theY = theTop + (className === "Arctic_Ocean" ? 15 : 20);
      }

      labelBg.setAttribute("x", theX);
      labelBg.setAttribute("y", theY);
      label.setAttribute("x", theX + 15);
      label.setAttribute("y", theY + 27);
    });
  }

  oceans.forEach((ocean) => {
    ocean.setAttribute("stroke", "#002459");
    ocean.setAttribute("stroke-width", "0.25");
    ocean.setAttribute("fill", "transparent");
    ocean.setAttribute("opacity", "1");

    ocean.addEventListener("mouseenter", function onMouseEnter(event) {
      const className = ocean.getAttribute("class");
      const labels = svgMap.querySelectorAll(`.g-${className}`);
      labels.forEach((label) => {
        const labelElement = label; // Create a variable instead of reassigning
        labelElement.style.display = "block";
      });
      hoverover(event, ocean, svgOffset);
    });

    ocean.addEventListener("mouseleave", function onMouseLeave() {
      const className = ocean.getAttribute("class");
      const labels = svgMap.querySelectorAll(`.g-${className}`);
      labels.forEach((label) => {
        const labelElement = label; // Create a variable instead of reassigning
        labelElement.style.display = "none";
      });
    });
  });

  svgMap.querySelectorAll("g.ocean path").forEach((path) => {
    const pathElement = path; // Create a variable instead of reassigning
    pathElement.style.display = "block";
  });
});
