{OVERALL_GAME_HEADER}

<div id="draft-wrapper">
  <div class="draft-title"><h4>{DRAFT}</h4></div>
  <div class="draft-title"><h4>{YOUR_HAND}</h4></div>
  <div id="draft-container"></div>
</div>
<div id="game-flow-panel"></div>
<div id="position-wrapper">
  <div id="main-boards">
    <div id="add-board-holder"><div id="add-board"></div></div>
    <div id="left-board-holder"><div id="left-board"></div></div>
    <div id="central-board-holder">
      <div id="central-board">
        <div id="harvest-slot-4" class="harvest-slot"></div>
        <div id="harvest-slot-7" class="harvest-slot"></div>
        <div id="harvest-slot-9" class="harvest-slot"></div>
        <div id="harvest-slot-11" class="harvest-slot"></div>
        <div id="harvest-slot-13" class="harvest-slot"></div>
        <div id="harvest-slot-14" class="harvest-slot"></div>
        <div id="modal-buttons-holder">
          <div id="majors-button"></div>
          <div id="hand-button"></div>
        </div>
      </div>
    </div>
  </div>
  <div id="player-boards"><div id="player-boards-left-column"><div id="player-boards-shape-outside"></div><div id="action-cards-holder"></div></div></div>
</div>
<div id="alternative-hand-wrapper"></div>


<svg style="display:none" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="map-marker-question" role="img" xmlns="http://www.w3.org/2000/svg">
  <symbol id="help-marker-svg" viewBox="0 0 512 512"><g class="fa-group"><path class="fa-secondary" fill="white" d="M256 8C119 8 8 119.08 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm0 422a46 46 0 1 1 46-46 46.05 46.05 0 0 1-46 46zm40-131.33V300a12 12 0 0 1-12 12h-56a12 12 0 0 1-12-12v-4c0-41.06 31.13-57.47 54.65-70.66 20.17-11.31 32.54-19 32.54-34 0-19.82-25.27-33-45.7-33-27.19 0-39.44 13.14-57.3 35.79a12 12 0 0 1-16.67 2.13L148.82 170a12 12 0 0 1-2.71-16.26C173.4 113 208.16 90 262.66 90c56.34 0 116.53 44 116.53 102 0 77-83.19 78.21-83.19 106.67z" opacity="1"></path><path class="fa-primary" fill="currentColor" d="M256 338a46 46 0 1 0 46 46 46 46 0 0 0-46-46zm6.66-248c-54.5 0-89.26 23-116.55 63.76a12 12 0 0 0 2.71 16.24l34.7 26.31a12 12 0 0 0 16.67-2.13c17.86-22.65 30.11-35.79 57.3-35.79 20.43 0 45.7 13.14 45.7 33 0 15-12.37 22.66-32.54 34C247.13 238.53 216 254.94 216 296v4a12 12 0 0 0 12 12h56a12 12 0 0 0 12-12v-1.33c0-28.46 83.19-29.67 83.19-106.67 0-58-60.19-102-116.53-102z"></path></g>
  </symbol>
</svg>


<svg style="display:none" aria-hidden="true" focusable="false" data-prefix="far" data-icon="expand-alt" role="img" xmlns="http://www.w3.org/2000/svg">
  <symbol id="expand-marker-svg" viewBox="0 0 320 512">
    <path fill="currentColor" d="M177 159.7l136 136c9.4 9.4 9.4 24.6 0 33.9l-22.6 22.6c-9.4 9.4-24.6 9.4-33.9 0L160 255.9l-96.4 96.4c-9.4 9.4-24.6 9.4-33.9 0L7 329.7c-9.4-9.4-9.4-24.6 0-33.9l136-136c9.4-9.5 24.6-9.5 34-.1z"></path>
  </symbol>
</svg>


<svg style="display:none" aria-hidden="true" focusable="false" data-prefix="far" data-icon="search-plus" role="img" xmlns="http://www.w3.org/2000/svg">
  <symbol id="zoom-svg" viewBox="0 0 512 512">
    <path fill="currentColor" d="M312 196v24c0 6.6-5.4 12-12 12h-68v68c0 6.6-5.4 12-12 12h-24c-6.6 0-12-5.4-12-12v-68h-68c-6.6 0-12-5.4-12-12v-24c0-6.6 5.4-12 12-12h68v-68c0-6.6 5.4-12 12-12h24c6.6 0 12 5.4 12 12v68h68c6.6 0 12 5.4 12 12zm196.5 289.9l-22.6 22.6c-4.7 4.7-12.3 4.7-17 0L347.5 387.1c-2.3-2.3-3.5-5.3-3.5-8.5v-13.2c-36.5 31.5-84 50.6-136 50.6C93.1 416 0 322.9 0 208S93.1 0 208 0s208 93.1 208 208c0 52-19.1 99.5-50.6 136h13.2c3.2 0 6.2 1.3 8.5 3.5l121.4 121.4c4.7 4.7 4.7 12.3 0 17zM368 208c0-88.4-71.6-160-160-160S48 119.6 48 208s71.6 160 160 160 160-71.6 160-160z"></path>
  </symbol>
</svg>

<script type="text/javascript">
var jstpl_configPlayerBoard = `
<div class='player-board' id="player_board_config">
  <div id="player_config" class="player_board_content">

    <div id="player_config_row">
      <div id="uwe-help"></div>
      <div id="help-mode-switch">
        <input type="checkbox" class="checkbox" id="help-mode-chk" />
        <label class="label" for="help-mode-chk">
          <div class="ball"></div>
        </label>

        <svg aria-hidden="true" focusable="false" data-prefix="fad" data-icon="question-circle" class="svg-inline--fa fa-question-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><g class="fa-group"><path class="fa-secondary" fill="currentColor" d="M256 8C119 8 8 119.08 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm0 422a46 46 0 1 1 46-46 46.05 46.05 0 0 1-46 46zm40-131.33V300a12 12 0 0 1-12 12h-56a12 12 0 0 1-12-12v-4c0-41.06 31.13-57.47 54.65-70.66 20.17-11.31 32.54-19 32.54-34 0-19.82-25.27-33-45.7-33-27.19 0-39.44 13.14-57.3 35.79a12 12 0 0 1-16.67 2.13L148.82 170a12 12 0 0 1-2.71-16.26C173.4 113 208.16 90 262.66 90c56.34 0 116.53 44 116.53 102 0 77-83.19 78.21-83.19 106.67z" opacity="0.4"></path><path class="fa-primary" fill="currentColor" d="M256 338a46 46 0 1 0 46 46 46 46 0 0 0-46-46zm6.66-248c-54.5 0-89.26 23-116.55 63.76a12 12 0 0 0 2.71 16.24l34.7 26.31a12 12 0 0 0 16.67-2.13c17.86-22.65 30.11-35.79 57.3-35.79 20.43 0 45.7 13.14 45.7 33 0 15-12.37 22.66-32.54 34C247.13 238.53 216 254.94 216 296v4a12 12 0 0 0 12 12h56a12 12 0 0 0 12-12v-1.33c0-28.46 83.19-29.67 83.19-106.67 0-58-60.19-102-116.53-102z"></path></g></svg>
      </div>
    </div>

    <div id="player_config_row">
      <div id="show-scores">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
          <g class="fa-group">
            <path class="fa-secondary" fill="currentColor" d="M0 192v272a48 48 0 0 0 48 48h352a48 48 0 0 0 48-48V192zm324.13 141.91a11.92 11.92 0 0 1-3.53 6.89L281 379.4l9.4 54.6a12 12 0 0 1-17.4 12.6l-49-25.8-48.9 25.8a12 12 0 0 1-17.4-12.6l9.4-54.6-39.6-38.6a12 12 0 0 1 6.6-20.5l54.7-8 24.5-49.6a12 12 0 0 1 21.5 0l24.5 49.6 54.7 8a12 12 0 0 1 10.13 13.61zM304 128h32a16 16 0 0 0 16-16V16a16 16 0 0 0-16-16h-32a16 16 0 0 0-16 16v96a16 16 0 0 0 16 16zm-192 0h32a16 16 0 0 0 16-16V16a16 16 0 0 0-16-16h-32a16 16 0 0 0-16 16v96a16 16 0 0 0 16 16z" opacity="0.4"></path>
            <path class="fa-primary" fill="currentColor" d="M314 320.3l-54.7-8-24.5-49.6a12 12 0 0 0-21.5 0l-24.5 49.6-54.7 8a12 12 0 0 0-6.6 20.5l39.6 38.6-9.4 54.6a12 12 0 0 0 17.4 12.6l48.9-25.8 49 25.8a12 12 0 0 0 17.4-12.6l-9.4-54.6 39.6-38.6a12 12 0 0 0-6.6-20.5zM400 64h-48v48a16 16 0 0 1-16 16h-32a16 16 0 0 1-16-16V64H160v48a16 16 0 0 1-16 16h-32a16 16 0 0 1-16-16V64H48a48 48 0 0 0-48 48v80h448v-80a48 48 0 0 0-48-48z"></path>
          </g>
        </svg>
      </div>

      <div id="show-help">
      <svg  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512">
        <g>
          <path d="m 233.2661,19.969492 -65.3,132.399998 -146.099998,21.3 c -26.2000003,3.8 -36.7,36.1 -17.7000003,54.6 l 105.6999983,103 -24.999998,145.5 c -4.5,26.3 23.199998,46 46.399998,33.7 l 130.7,-68.7 130.7,68.7 c 23.2,12.2 50.9,-7.4 46.4,-33.7 l -25,-145.5 105.7,-103 c 19,-18.5 8.5,-50.8 -17.7,-54.6 l -146.1,-21.3 -65.3,-132.399998 c -11.7,-23.6000005 -45.6,-23.9000005 -57.4,0 z"
             style="fill:currentColor;fill-opacity:0.4;stroke-width:0.50514001" />
          <path style="fill:currentColor"
             d="m 266.54954,154.03389 c -41.43656,0 -68.27515,16.07436 -89.34619,44.74159 -3.82236,5.20034 -2.64393,12.33041 2.68806,16.15841 l 22.3943,16.0773 c 5.38496,3.86585 13.04682,2.96194 17.26269,-2.03884 13.00373,-15.42456 22.64971,-24.30544 42.96178,-24.30544 15.97056,0 35.72456,9.73171 35.72456,24.39489 0,11.08489 -9.66467,16.77774 -25.43382,25.14841 -18.3892,9.7617 -42.72402,21.91024 -42.72402,52.30077 v 4.81105 c 0,6.51516 5.57808,11.7966 12.45917,11.7966 h 37.62199 c 6.88108,0 12.45915,-5.28144 12.45915,-11.7966 v -2.83758 c 0,-21.06678 65.03059,-21.94415 65.03059,-78.95226 5.2e-4,-42.93179 -47.03384,-75.4983 -91.09826,-75.4983 z m -5.20222,183.56459 c -19.82875,0 -35.96076,15.27415 -35.96076,34.04846 0,18.77381 16.13201,34.04797 35.96076,34.04797 19.82875,0 35.96077,-15.27416 35.96077,-34.04846 0,-18.7743 -16.13202,-34.04797 -35.96077,-34.04797 z" />
        </g>
      </svg>
      </div>

      <div id="show-settings">
        <svg  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512">
          <g>
            <path class="fa-secondary" fill="currentColor" d="M638.41 387a12.34 12.34 0 0 0-12.2-10.3h-16.5a86.33 86.33 0 0 0-15.9-27.4L602 335a12.42 12.42 0 0 0-2.8-15.7 110.5 110.5 0 0 0-32.1-18.6 12.36 12.36 0 0 0-15.1 5.4l-8.2 14.3a88.86 88.86 0 0 0-31.7 0l-8.2-14.3a12.36 12.36 0 0 0-15.1-5.4 111.83 111.83 0 0 0-32.1 18.6 12.3 12.3 0 0 0-2.8 15.7l8.2 14.3a86.33 86.33 0 0 0-15.9 27.4h-16.5a12.43 12.43 0 0 0-12.2 10.4 112.66 112.66 0 0 0 0 37.1 12.34 12.34 0 0 0 12.2 10.3h16.5a86.33 86.33 0 0 0 15.9 27.4l-8.2 14.3a12.42 12.42 0 0 0 2.8 15.7 110.5 110.5 0 0 0 32.1 18.6 12.36 12.36 0 0 0 15.1-5.4l8.2-14.3a88.86 88.86 0 0 0 31.7 0l8.2 14.3a12.36 12.36 0 0 0 15.1 5.4 111.83 111.83 0 0 0 32.1-18.6 12.3 12.3 0 0 0 2.8-15.7l-8.2-14.3a86.33 86.33 0 0 0 15.9-27.4h16.5a12.43 12.43 0 0 0 12.2-10.4 112.66 112.66 0 0 0 .01-37.1zm-136.8 44.9c-29.6-38.5 14.3-82.4 52.8-52.8 29.59 38.49-14.3 82.39-52.8 52.79zm136.8-343.8a12.34 12.34 0 0 0-12.2-10.3h-16.5a86.33 86.33 0 0 0-15.9-27.4l8.2-14.3a12.42 12.42 0 0 0-2.8-15.7 110.5 110.5 0 0 0-32.1-18.6A12.36 12.36 0 0 0 552 7.19l-8.2 14.3a88.86 88.86 0 0 0-31.7 0l-8.2-14.3a12.36 12.36 0 0 0-15.1-5.4 111.83 111.83 0 0 0-32.1 18.6 12.3 12.3 0 0 0-2.8 15.7l8.2 14.3a86.33 86.33 0 0 0-15.9 27.4h-16.5a12.43 12.43 0 0 0-12.2 10.4 112.66 112.66 0 0 0 0 37.1 12.34 12.34 0 0 0 12.2 10.3h16.5a86.33 86.33 0 0 0 15.9 27.4l-8.2 14.3a12.42 12.42 0 0 0 2.8 15.7 110.5 110.5 0 0 0 32.1 18.6 12.36 12.36 0 0 0 15.1-5.4l8.2-14.3a88.86 88.86 0 0 0 31.7 0l8.2 14.3a12.36 12.36 0 0 0 15.1 5.4 111.83 111.83 0 0 0 32.1-18.6 12.3 12.3 0 0 0 2.8-15.7l-8.2-14.3a86.33 86.33 0 0 0 15.9-27.4h16.5a12.43 12.43 0 0 0 12.2-10.4 112.66 112.66 0 0 0 .01-37.1zm-136.8 45c-29.6-38.5 14.3-82.5 52.8-52.8 29.59 38.49-14.3 82.39-52.8 52.79z" opacity="0.4"></path>
            <path class="fa-primary" fill="currentColor" d="M420 303.79L386.31 287a173.78 173.78 0 0 0 0-63.5l33.7-16.8c10.1-5.9 14-18.2 10-29.1-8.9-24.2-25.9-46.4-42.1-65.8a23.93 23.93 0 0 0-30.3-5.3l-29.1 16.8a173.66 173.66 0 0 0-54.9-31.7V58a24 24 0 0 0-20-23.6 228.06 228.06 0 0 0-76 .1A23.82 23.82 0 0 0 158 58v33.7a171.78 171.78 0 0 0-54.9 31.7L74 106.59a23.91 23.91 0 0 0-30.3 5.3c-16.2 19.4-33.3 41.6-42.2 65.8a23.84 23.84 0 0 0 10.5 29l33.3 16.9a173.24 173.24 0 0 0 0 63.4L12 303.79a24.13 24.13 0 0 0-10.5 29.1c8.9 24.1 26 46.3 42.2 65.7a23.93 23.93 0 0 0 30.3 5.3l29.1-16.7a173.66 173.66 0 0 0 54.9 31.7v33.6a24 24 0 0 0 20 23.6 224.88 224.88 0 0 0 75.9 0 23.93 23.93 0 0 0 19.7-23.6v-33.6a171.78 171.78 0 0 0 54.9-31.7l29.1 16.8a23.91 23.91 0 0 0 30.3-5.3c16.2-19.4 33.7-41.6 42.6-65.8a24 24 0 0 0-10.5-29.1zm-151.3 4.3c-77 59.2-164.9-28.7-105.7-105.7 77-59.2 164.91 28.7 105.71 105.7z"></path>
          </g>
        </svg>
      </div>
    </div>
    <div class='settingsControlsHidden' id="settings-controls-container">
      <div class="settings-section" id="settings-section-layout">
        <div class="settings-section-heading">\${sectionLayout}</div>

        <div class='row-data row-data-large'>
          <div class='row-label'>\${centralBoardSize}</div>
          <div class='row-value'>
            <div id="layout-control-central-board-size"></div>
          </div>
        </div>

        <div class='row-data row-data-large'>
          <div class='row-label'>\${playerBoardSize}</div>
          <div class='row-value'>
            <div id="layout-control-player-board-size"></div>
          </div>
        </div>

        <div class='row-data row-data-large'>
          <div class='row-label'>\${cardSize}</div>
          <div class='row-value'>
            <div id="layout-control-card-size"></div>
          </div>
        </div>
      </div>

      <div class="settings-section" id="settings-section-gameflow">
        <div class="settings-section-heading">\${sectionGameFlow}</div>

        <div class='row-data row-data-large'>
          <div class='row-label'>\${playCard}</div>
          <div class='row-value'>
            <div id="layout-control-card-animation">
              <svg aria-hidden="true" focusable="false" data-prefix="fal" data-icon="turtle" class="svg-inline--fa fa-turtle fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M464 128c-8.84 0-16 7.16-16 16s7.16 16 16 16 16-7.16 16-16-7.16-16-16-16zm81.59-19.77C510.52 83.36 487.21 63.89 458.64 64c-70.67.28-74.64 70.17-74.64 73v71.19c-.02 6.89-2.07 13.4-4.99 19.59C306.47-5.44 87.67 22.02 33.15 241.28c-1.28 5.16-1.28 10.24-.52 15.11C14.53 257.47 0 272.21 0 290.59c0 14.91 9.5 28.11 23.66 32.81l47.69 15.91L36.31 400c-5.78 10.02-5.78 21.98 0 32s16.16 16 27.72 16h36.94c11.38 0 22-6.12 27.72-16l33.88-58.66C183.78 379.75 204.75 384 240 384s56.22-4.25 77.44-10.66l33.88 58.69c5.72 9.84 16.34 15.97 27.72 15.97h36.94c11.56 0 21.94-5.98 27.72-16 5.78-10.02 5.78-21.98 0-32l-38.47-66.64c17.81-9.58 32.88-22.28 44.91-37.91 12.75-16.58 21.47-35.19 26.03-55.45h27.19c40.06 0 72.66-32.59 72.66-72.66-.02-23.39-11.4-45.48-30.43-59.11zM351.8 249.01c.89 3.59-1.52 6.99-4.04 6.99H68.25c-2.53 0-4.93-3.42-4.04-7 50.42-202.79 236.99-203.48 287.59.01zM503.34 208h-54.75l-1.75 14c-2.53 20.03-9.97 38.17-22.09 53.94-19.88 25.87-43.07 33.45-65.25 42.25L415.97 416H379l-46.75-81.05C303.17 344.49 284.62 352 240 352c-45.86 0-64.64-8-92.25-17.05L100.97 416H64l54.66-94.63L32 288h303.06c29.22 0 51.64-15.08 64.38-31.59 10.78-14.05 16.53-30.7 16.56-48.19V137c0-26.99 22.44-40.55 42.26-41 19.93-.45 36.75 15.44 68.71 38.26 10.66 7.62 17.03 20 17.03 33.08 0 22.43-18.25 40.66-40.66 40.66z"></path></svg>

              <div id="layout-control-card-animation-speed"></div>

              <svg aria-hidden="true" focusable="false" data-prefix="fal" data-icon="rabbit-fast" class="svg-inline--fa fa-rabbit-fast fa-w-20" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="currentColor" d="M511.99 223.99c-8.84 0-16 7.16-16 16s7.16 16 16 16 16-7.16 16-16c0-8.83-7.16-16-16-16zm90.89-32.78c-.61-.43-58.52-35.99-58.52-35.99-2.54-1.57-9.73-5.07-18.35-7.52a261.57 261.57 0 0 0-4.89-22.02c-5.89-21.97-28.67-93.67-74.52-93.68h-.01c-37.96 0-44.2 41.84-44.49 43.33-32.09-17.15-55.46-13.15-69.7 1.09-8.71 8.71-20.55 28.35-4.36 63.28-31.09-16.38-61.55-27.7-88.06-27.7-45.73 0-86.28 18.33-117.89 52.43C108.58 151.29 90.85 144 71.97 144c-19.23 0-37.32 7.49-50.91 21.09-28.07 28.07-28.07 73.75 0 101.82C34.66 280.51 52.74 288 71.98 288c12.73 0 24.8-3.57 35.51-9.8 3.59 6.33 7.69 12.45 12.83 18.02l54.04 58.54-25.01 13.52a47.925 47.925 0 0 0-21.38 39.94v23.73c0 17.3 8.94 32.83 23.91 41.51 7.53 4.36 15.81 6.55 24.1 6.55 8.16 0 16.34-2.12 23.81-6.39l55.19-31.53 25.49 27.61a32.008 32.008 0 0 0 23.52 10.29H464c17.68 0 32-14.33 32-32 0-35.29-28.71-64-64-64h-48l70.4-32h96.96c48.88 0 88.65-39.77 88.65-88.65-.01-28.56-13.89-55.53-37.13-72.13zM96.26 246.93c-24.53 19.16-46.88 3.04-52.58-2.65-15.62-15.62-15.62-40.95 0-56.57 15.61-15.61 40.95-15.63 56.57 0 1.31 1.31 2.21 2.83 3.25 4.27-7.81 17.43-10.34 36.49-7.24 54.95zm87.65 198.9c-10.53 6.09-23.94-1.52-23.94-13.89v-23.73c0-5.36 2.66-10.34 5.84-12.55L196.74 379l35.96 38.96-48.79 27.87zm367.44-125.84H447.99l-64 26.67v-2.26c0-49.75-33.41-94.03-81.22-107.68l-42.38-12.11c-20.46-5.8-29.09 24.97-8.81 30.78l42.38 12.11c34.19 9.75 58.04 41.37 58.04 76.9v71.59h80c17.66 0 32 14.36 32 32H303.98L143.83 274.51c-22.36-24.22-22.66-61.37-.81-86.06 20.15-22.76 51.33-44.45 96.96-44.45 57.33 0 152.74 75.22 208.01 111.99 0-31.16-.53-30.77 3.54-43.01-15.31-3.53-37.75-17.86-59.17-39.28-30.93-30.92-47.64-64.35-37.33-74.65 10.74-10.74 45.14 7.8 74.66 37.33 3.25 3.25 6.25 6.54 9.18 9.81-11.63-44.51-8.08-82.19 7.72-82.19 13.94 0 32.92 30.05 43.61 69.97 4.1 15.28 6.36 29.86 6.98 42.49 14.17-1.01 24.77 3.23 30.44 6.03l56.65 34.75a56.632 56.632 0 0 1 23.72 46.1c.01 31.29-25.36 56.65-56.64 56.65z"></path></svg>
            </div>
          </div>
        </div>
      </div>

      <div class="settings-section" id="settings-section-accessibility">
        <div class="settings-section-heading">\${sectionAccessibility}</div>
      </div>
    </div>
  </div>
</div>
`;



var jstpl_scoresModal = `
<table id='players-scores'>
  <thead>
    <tr id="scores-headers">
      <th></th>
    </tr>
  </thead>
  <tbody id="scores-body">
    <tr id="scores-row-fields">
      <td class="row-header">\${fields}</td>
    </tr>
    <tr id="scores-row-pastures">
      <td class="row-header">\${pastures}</td>
    </tr>
    <tr id="scores-row-grains">
      <td class="row-header">\${grains}</td>
    </tr>
    <tr id="scores-row-vegetables">
      <td class="row-header">\${vegetables}</td>
    </tr>
    <tr id="scores-row-sheeps">
      <td class="row-header">\${sheeps}</td>
    </tr>
    <tr id="scores-row-pigs">
      <td class="row-header">\${pigs}</td>
    </tr>
    <tr id="scores-row-cattles">
      <td class="row-header">\${cattles}</td>
    </tr>
    <tr id="scores-row-empty">
      <td class="row-header">\${empty}</td>
    </tr>
    <tr id="scores-row-stables">
      <td class="row-header">\${stables}</td>
    </tr>
    <tr id="scores-row-clayRooms">
      <td class="row-header">\${clayRooms}</td>
    </tr>
    <tr id="scores-row-stoneRooms">
      <td class="row-header">\${stoneRooms}</td>
    </tr>
    <tr id="scores-row-farmers">
      <td class="row-header">\${farmers}</td>
    </tr>
    <tr id="scores-row-beggings">
      <td class="row-header">\${beggings}</td>
    </tr>
    <tr id="scores-row-cards">
      <td class="row-header">\${cards}</td>
    </tr>
    <tr id="scores-row-cardsBonus">
      <td class="row-header">\${cardsBonus}</td>
    </tr>

    <tr id="scores-row-total">
      <td class="row-header">\${total}</td>
    </tr>
  </tbody>
</table>
`;


</script>

{OVERALL_GAME_FOOTER}
