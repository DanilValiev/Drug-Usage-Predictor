let map;
var modal = document.getElementById("myModal");
var ratingProgressBar = document.getElementById("progress");
var span = document.getElementsByClassName("close")[0];

span.onclick = function() {
  modal.style.display = "none";
  map.setZoom(13);
  window.setTimeout(() => {
    map.panTo(marker.getPosition());
  }, 10000);
}

function initMap() {
  submit();
  document.getElementById('rating').innerHTML = 4;

    const position = { lat: 61.49, lng: 23.76 };
    const mapZoom = 13;
    map = new google.maps.Map(document.getElementById("map"), {
    center: position,
    zoom: mapZoom,
    mapTypeControl: false,
    styles: mapStyle.styles,
    minZoom: mapZoom,
    restriction: {
      latLngBounds: {
        north: 61.5503,
        south: 61.4194,
        west: 23.5,
        east: 24,
    },
  },
  });
 
};
async function getParams(lat, lng){
  let response = await fetch('https://tech.mcsgg.ru/hackathon/index.php?token=LexaPidor&type=get_pro&lat=' + lat + '&lng=' + lng);
  let result = await response.json();
  document.getElementById('streetTitle').innerHTML = result.adress;
  document.getElementById('rating').innerHTML = parseInt(result.rating/20);
  ratingProgressBar.style.width = result.rating*2 + "px";

  const myNode = document.getElementById("userViewId");
  myNode.innerHTML = '';
  for(var people in result.members){
    const div = document.createElement('div');

    div.className = 'userCard';
    
    let style = (result.members[people].tags.length != 0) ? "height: 98px;" : "";
    let tags = '';
    // let style = '';
    // console.log(result.members[people].tags);
    if (result.members[people].tags.length != 0)
    {
      tags = `<div class="tags">`;
      let i = 0;
      result.members[people].tags.forEach(element => {
          if (i <= 2)
          {
            tags += `<div class="visibleTagBackgraund">`;
            tags += `<title class="visibleTagTitle">`+element+`</title>`;
            tags += `</div>`
          }
          i++;
      });
      tags += `</div>`;
    }

    div.innerHTML = `
    <div class="userCard">
        <title style="

        display: block;
        color: black;
        font-size: 26px;
    
        margin-left: 33px;
        font-weight: 500;
        ">` + result.members[people].name + `, ` + result.members[people].age+  `</title>
         <div class="userDescription" style="display: flex;margin-left: 33px;font-size: 16px;color: #3b3b3b;">
            <p>Risk:</p>
            <div class="userProgress" style="
                 margin-left: 15px;
                 background-color:  #C4C4C4;
                  max-height: 16px;
                 width: 300px;
                 font-size: 18px;
                 text-align: center;
                  margin-top: 2px;">
                <div style="
                background-color: #EE8282;
    width: 100%;
    z-index: 4;
    max-width: 300px;
    width: ` + result.members[people].rating*2 +`px;
    height: 16px;"></div>
               
            </div>
        </div>
        ` + tags + `
    </div>
    `;
  
    document.getElementById('userViewId').appendChild(div);
  }
}

async function submit() {
  let response = await fetch('https://tech.mcsgg.ru/hackathon/index.php?token=LexaPidor&type=all');
  let result = await response.json();
  for(var house in result){
    const pos = new google.maps.LatLng(result[house].location.lat, result[house].location.lng);
    const marker = new google.maps.Marker({
      position: pos,
      icon: "./styles/images/" +  parseInt(result[house].rating/20) + ".png",
      map: map,
    });
    marker.addListener("click", () => {
      map.setZoom(17);
      map.setCenter(marker.getPosition());
      modal.style.left = "0px";
      modal.style.display = "block";
      getParams(marker.getPosition().lat(), marker.getPosition().lng());
      window.setTimeout(() => {
        map.panTo(marker.getPosition());
      }, 100);
      });
  }
};
