{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- arnak implementation : © Adam Spanel adam.spanel@seznam.cz
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

    arnak_arnak.tpl
    
    This is the HTML template of your game.
    
    Everything you are writing in this file will be displayed in the HTML page of your game user interface,
    in the "main game zone" of the screen.
    
    You can use in this template:
    _ variables, with the format {MY_VARIABLE_ELEMENT}.
    _ HTML block, with the BEGIN/END format
    
    See your "view" PHP file to check how to set variables and control blocks
    
    Please REMOVE this comment before publishing your game on BGA
-->

<div class='arnak-wrap'>
    <div class='player-area'>
        <div class='player-camp'>
            <div class='camp-background'></div>
            <div class='idol-bonus bonus-jewel' data-bonus="jewel"></div>
            <div class='idol-bonus bonus-arrowhead' data-bonus="arrowhead"></div>
            <div class='idol-bonus bonus-tablet' data-bonus="tablet"></div>
            <div class='idol-bonus bonus-coins' data-bonus="coins"></div>
            <div class='idol-bonus bonus-card' data-bonus="card"></div>
            <div class='idol-highlight-box'></div>
            <div class='deck-amt'>1</div>
        </div>
    </div>
    <div class='arnak-board'>
        <div class='staff-parent'>
            <div class='staff' id='arn-staff'></div>
        </div>
        <div class='item-info-wrap'>
            <div class='item-deck'>{DECK}<span class='item-deck-number'>0</span>
            </div>
            <div class='item-exile'>{EXILE}<span class='item-exile-number'>0</span>
            </div>
        </div>
        <div class='art-info-wrap'>
            <div class='art-deck'>{DECK}<span class='art-deck-number'>0</span>
            </div>
            <div class='art-exile'>{EXILE}<span class='art-exile-number'>0</span>
            </div>
        </div>
    </div>
    <div id='playeraid'></div>
</div>
<svg height="0" width="0">
    <defs>
        <clipPath id="staff-path"> 
            <path d="M104.78.33c1.63,1.63,2.36,5.65,2.52,8l-1.8,6.6a7.38,7.38,0,0,0,.6,4.08c-1.18,3.72-1.72,13.52-2.28,13.68-1.22,1.74-1.52,3.21-2.52,5.52L92.9,46.8c-.12.76-7.14,5.62-12.2,8.25-1.48,4.4-6.71,17.93-9,23.39-1.24,3.84-.65,13.87-.21,14.31l.36,9.6c2.55,5,1.51,9.56-.22,18.56-1.79,9.29-3.24,18.91-5.9,26.55-2.07,6,4.1,12.82,2.4,19.8-3,12.14.16,16.27,2.28,26.88.79,4-.87,10.22-2.16,12.6-.65,1.19.65,9.23,1,13.24s-1,8.92-1.1,8.6c-.2.08.45,3.46.25,3.54,0,5.53.83,13,.23,14.21-1.38,5.45.06,9.93-.72,13.92s-3.39,2.83-4.2,9c-2,15.15,5.87,31-.12,43.68-5.31-1-16.94,1.71-19.19-2.16l-1.08-19.2-2.76-18.36c-.47-3.27.43-7,.72-9.24l-.36-10.2c1.06-9.13,1.52-18.21,2.76-27.83.57-4.4-1.38-4.83-1.81-7.68,0-2.84-.71-5.68-.71-8.52-.93-5.51-2.84-9.31-3.85-15.72l-1.08-16.08a124.48,124.48,0,0,1-2.79-15.59c-1.14-8.61.56-11.14,1.82-13.95-.12-.24.14-12.21.62-17.49.23-1.14-2.26-26.24-2.3-26.28-4.15.21-.72-20.23-2.13-25.34-2.58-2.83-4.94-13.91-4-19.56.34-2.1-4.56-3.73-5.44-5.85C20.33,50.08,17,48.81,14.44,46c-1.72-3.12-4.14-6.24-5.86-9.36-1.86-2.47-4.9-4.33-6-7.2.28-1.4-.15-2.8.13-4.2-1-8.37-4.49-16.42-.84-24.14C10.51,10.22,26,22.69,41.81,23.83c2.65.19,5.27,1.51,8.4.13.6.81,1.1.87,2.28,1.1,6.23-1,12.47-.67,18.71-1.71L74,20.83c1.4-.2,2.8-1.1,4.2-1.3,2.8-1.29,15.58-8.86,18.11-10.9s3.31-4,5.52-6.14Z"
            transform="scale(2 2)">
        </clipPath>
    </defs>
</svg>

<svg height="0" width="0">
    <defs>
        <clipPath id="location-small-path"> 
            <path d="M170.33,165V27a15.72,15.72,0,0,0-13.19-15.51v0L85.29.25,13.44,11.41v0A15.71,15.71,0,0,0,.25,27V59.88h0V191.52A15.71,15.71,0,0,0,13.44,207v0l55.5,8.63a21.23,21.23,0,0,1,32.7,0l55.5-8.63v0a15.72,15.72,0,0,0,13.19-15.51V165Z"
            transform="scale(2 2)">
        </clipPath>
    </defs>
</svg>
<svg height="0" width="0">
    <defs>
        <clipPath id="location-big-path"> 
            <path d="M204.34,181.67V29.74a15.69,15.69,0,0,0-13.18-15.48v0L102.3.25l-88.86,14v0A15.69,15.69,0,0,0,.25,29.74v30h0V207.13a15.68,15.68,0,0,0,13.19,15.48v0l63.89,10.05c2.43-3.85,12.68-9.58,25-9.58s22.54,5.73,25,9.58l63.9-10.05v0a15.69,15.69,0,0,0,13.18-15.48V181.67Z"
            transform="scale(2 2)">
        </clipPath>
    </defs>
</svg>
<svg height="0" width="0">
    <defs>
        <clipPath id="guardian-path"> 
            <path d="M203.84,181.42V29.5a15.37,15.37,0,0,0-13-15.24l-.21,0q-44.32-7-88.65-14l-88.58,14-.2.06A15.38,15.38,0,0,0,.25,29.5V181.42c0,11.06,14.41,20.86,16.61,22.29l5.58-19.52,0-.18H181.61l0,.18q2.79,9.76,5.57,19.52C189.44,202.28,203.84,192.48,203.84,181.42Z"
            transform="scale(2 2)">
        </clipPath>
    </defs>
</svg>
<svg height="0" width="0">
    <defs>
        <clipPath id="idol-path"> 
            <path d="M.25,60.82V15.7c.87-.91,10.56-10.64,31-15.44,20.49,4.85,30,14.53,30.87,15.44V60.86c-.86.91-10.4,10.59-31,15.45C9.89,71.28.28,60.89.19,60.79"
            transform="scale(2 2)">
        </clipPath>
    </defs>
</svg>
<svg height="0" width="0">
    <defs>
        <clipPath id="temple-bronze-path"> 
            <path d="M113.32,15.45c.26-3.14.31-4.22.31-4.22V7.86c0-.9,0-.93-2.1-3.08a19.44,19.44,0,0,0-5.13-3.6L104.64.5A19.71,19.71,0,0,0,99.45.36C96.7.33,86.2.39,86.2.39s-9,.22-12.93.28S55.06,1.12,49.58,1,34.38.36,34.38.36l-1.62,0a10.27,10.27,0,0,0-2.18,1.67A2.85,2.85,0,0,1,28,3.62,18,18,0,0,1,24.48.76,5.35,5.35,0,0,0,22.27.44c-1.7,0-7.21,0-10.24.17s-6,.37-8,1.17a9.72,9.72,0,0,0-3,3.85,21.17,21.17,0,0,0-.68,6.25,75.19,75.19,0,0,1,.85,9.8A63.93,63.93,0,0,1,.94,30a39.78,39.78,0,0,0-.68,6A47.19,47.19,0,0,0,1,45.16a19,19,0,0,0,3.35,2.23c1.14.6,1.42.63,1.48,2,.31,1.51,1.78,1.68,4.22,1.79A54.55,54.55,0,0,0,16.2,51c4.25-.36,9-1.05,9-1.05a60.8,60.8,0,0,1,7.35-.06c3.83.23,9.92,1.22,9.92,1.22l12.66-.51c5.19-.2,9.41-.42,9.41-.42a35.88,35.88,0,0,1,12.77,1c3.06.2,4.42-.14,5.24-1.13a4.2,4.2,0,0,1,3.52-1.59c3.24,2.46,3.55,2.78,3.55,2.78l7.51,0s3.15-.56,7.72-1.19,5.62,0,6.58-1.52a14.33,14.33,0,0,0,2.18-5.38,48.13,48.13,0,0,1-.08-5.47c-.09-1.5-.45-6.88-.45-9.71S113.32,15.45,113.32,15.45Z"
            transform="scale(2 2)">
        </clipPath>
    </defs>
</svg>
<svg height="0" width="0">
    <defs>
        <clipPath id="temple-silver-path"> 
            <path d="M112,6c.11-3.34,1-6-8.67-5.21S91,2.43,89,1.53C86.65-.34,83.48-.23,78,2.32c-10.13,0-11-.57-15.35-1.19A38,38,0,0,0,51.76.79c-4.65,1.3-6.23,2.15-16,.57C25.14.56,30.91,1.07,24.85,1S9.78-.23,3.38.9C3,11.55.49,12.63.44,16.65s2.15,4,2.26,6.12A3.83,3.83,0,0,1,1,26.05S.83,39.7.27,46.5c-.17,4.24.9,5.15,1.41,6A7.78,7.78,0,0,1,2.36,57c.68,2.32,1,3.51,2.89,3.79s7,1.65,7,1.65,14.05.28,23.45.05S54.48,62,56.29,62c3.46-.12,3.57-2.32,5.66-2.21s7.71,1.13,10,2.72c17.45-.63,19.77-1.19,22.43-1.59S100,61.33,104,62s6.12,1.13,6.18-1.36-.85-5,2.15-12.57c1.19-1.65,1-3.29.9-6.91s.91-13.6,0-20.28S112,6,112,6Z"
            transform="scale(2 2)">
        </clipPath>
    </defs>
</svg>
<svg height="0" width="0">
    <defs>
        <clipPath id="temple-gold-path"> 
            <path d="M112.1,5.53c-.51-4.43.11-3.69-10.48-4.6S80.25,1.27,65.57.54C56.05.2,56,.93,54.64.93c-6.12,1.82-7,1.65-8.62,1.14S41.94.2,26.7.25C22.9.37,18,.88,18,.88S8,1,.8,3.09C.23,24.49.52,26.93.46,30.33s1.25,2.45.45,8.86A68.52,68.52,0,0,0,.74,56.78a23.39,23.39,0,0,1-.11,9.43c-.51,3.35.51,5.22,5,6s5.66,1.54,5.66,1.54h8.33c3.86-2.5,5.11-2.56,9.75,0C52.6,73.59,50.1,73.7,54,73.42s13-.17,23.81.11c12.52.28,16.26.23,23.34.4s13.15,0,12.41-19.81c-2.66-2.27-1.47-6.47-.73-7.89s1-1.76.17-6a90.5,90.5,0,0,1-.29-11.69s.4-8.86.12-12.78S112.1,5.53,112.1,5.53Z"
            transform="scale(2 2)">
        </clipPath>
    </defs>
</svg>
<svg height="0" width="0">
    <defs>
        <clipPath id="path-start-player"> 
            <path d="M130.26,45.4a30.73,30.73,0,0,0-5.8-37,30.38,30.38,0,0,0-40-1.18,67.85,67.85,0,0,0-32.38,0,30.38,30.38,0,0,0-40,1.18,30.71,30.71,0,0,0-5.79,37,68.63,68.63,0,0,0,16.25,78.84c-1.43,5.32.81,10.63,5.19,12a8.34,8.34,0,0,0,8.34-2.41,67.81,67.81,0,0,0,64.39,0,8.34,8.34,0,0,0,8.34,2.41c4.38-1.39,6.61-6.7,5.18-12A68.67,68.67,0,0,0,130.26,45.4"
            transform="scale(2 2)">
        </clipPath>
    </defs>
</svg>

{OVERALL_GAME_FOOTER}
