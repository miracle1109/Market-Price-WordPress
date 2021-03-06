/* $Hyena v2.1 jQuery Plugin || Author: Crusader12 */
String.prototype.P=Number.prototype.P=function(){return parseFloat(this);};
String.prototype.S=function(key){return this.toString().split(',')[key];};
(function($){
	var Hyena={
		defaults:{
			sts:false, 					// PLAY STATE
			controls:true,				// USE MEDIA CONTROLS
			style:1, 					// PLAYER STYLE
			control_opacity:'0,0.9',	// BUTTON OPACITY
			fade_speed:'250,250',		// BUTTON FADE SPEED
			player_fade_speed:1000,		// INTIAL PLAYER FADEIN SPEED
			show_button:true,			// SHOW PLAY BUTTON INITIALLY
			on_timer:'0,0',				// TIMER DELAY, TIMER DURATION
			on_scroll:false,			// PLAY WHEN SCROLLED INTO VIEWPORT
			scroll_offset:0.15,			// PERCENTAGE OF WINDOW TO OFFSET
			on_hover:false,				// PLAY ON HOVER
			slate:'350,0.65,0'			// SLATE SPEED, SLATE OPACITY, SLATE TILE
		},

		/* INITIALIZATION */
		init:function(options){
			/* DROP OUT IF CANVAS IS NOT SUPPORTED */
			var e=document.createElement('canvas');
			if(!(!!(e.getContext && e.getContext('2d')))) return;

			// MERGE MAIN USER OPTIONS WITH DEFAULTS
			var mergedData=$.extend({}, Hyena.defaults, options);

			// LOOP THROUGH ALL HYENA GIFS
			for(var i=0, l=this.length; i<l; i++){
				var $t=$(this[i]),
					$d=$t.data('hyena'),
					hD=$d!=undefined ? $d : false,
					$S=$.support.Hyena;
				// MERGE DATA FROM DEFAULTS + GIF -> REASSIGN TO THIS GIF - + CURRENT SOURCE AS DATA ATTR */
				$.data($t, $.extend({}, mergedData, !hD?{} : hD||{}));
				$t.data('hyena',$.data($t))
				var D=$t.data().hyena;

				// SETUP PLAYER SKIN AND ADD SLATE
				$t.wrap('<div class="hy_plyr hy_plyr_'+D.style+'"><div class="hy_fr_'+D.style+'"/>');

				var $P=$t.parents('div.hy_plyr:first');
				$P.prepend('<div class="hy_bt_wr"><div class="hyena_slate"></div></div>');

				// ASSIGN TILE
				if(D.slate.S(2).P()>0) $P.find('div.hyena_slate').css('background','url(Hyena/controls/tiles/bg_'+D.slate.S(2).P()+'.png)');

				// CHECK SETTINGS
				if(D.on_scroll){ D.on_hover=false; D.controls=false; };
				if(D.on_hover){ D.controls=false; if($S.isTablet)D.on_hover=false; };

				// ON_TIMER SETTINGS
				D.tmrOn=D.on_timer.S(0).P();
				if(D.tmrOn>0){
					D.tmr_Off=D.on_timer.S(1).P();
					D.show_button=false;
					D.controls=false;
				};

				// SETUP OPTIONAL USER CONTROLS
				if(D.controls){
					$P.find('div.hy_bt_wr').prepend('<img src="Hyena/controls/'+D.style+'_play.png" class="hy_btn"/>');

					////////////////////////////////////////
					// BUTTON OPACITY CHANGE ON PLAYER HOVER
					////////////////////////////////////////
					$P.on('mouseenter',function(){
						var $this=$(this),
							$T=$this.find('img[src*=".gif"]'),//Edited
							D=$T.data().hyena,
							$B=$this.find('img.hy_btn'),
							curOp=$B.css('opacity').P(),
							newOp=D.control_opacity.S(1).P();
						if(curOp!==newOp){
							$B.stop(true,false).animate({opacity:D.control_opacity.S(1).P()},{duration:D.fade_speed.S(1).P(),queue:false});
						};
					}).on('mouseleave',function(){
						var $B=$(this).find('img.hy_btn'),
							curOp=$B.css('opacity').P(),
							newOp=D.control_opacity.S(0).P();
						if(curOp!==newOp){
							$B.stop(true,false).animate({opacity:D.control_opacity.S(0).P()},{duration:D.fade_speed.S(0).P(),queue:false});
						};
					});


					/////////////
					// START/STOP
					/////////////
					$P.find('div.hy_bt_wr').css('cursor','pointer').on($S.cEv,function(e){
						if(!e.handled){
							var $P=$(this).parents('div.hy_plyr:first'),
								D=$P.find('img[src*=".gif"]').data().hyena;//Edited

							if(D.tmrOn>0) return;

							// PLAY
							if(!D.sts){
								// ANIMATE OPACITY AND CHANGE TO STOP BUTTON
								$P.find('img.hy_btn').stop(true,false).animate({opacity:D.control_opacity.S(0).P()},
									{duration:D.fade_speed.S(0).P(),queue:false,complete:function(){
										$(this).attr('src','Hyena/controls/'+D.style+'_stop.png');
									}});
								Hyena.PL($P,D);
								// STOP
							}else{
								Hyena.ST($P,D,$(this),false);
							};
						};
						return false;
					});

					/////////////////////////////
					// NO CONTROLS - HOVER EVENTS
					/////////////////////////////
				}else if(D.on_hover){
					$P.on('mouseenter touchstart',function(){
						var $P=$(this),
							D=$P.find('img[src*=".gif"]').data().hyena;//Edited
						if(D.sts)return;
						Hyena.PL($P,D);

					}).on('mouseleave touchend',function(){
						var $P=$(this),
							D=$P.find('img[src*=".gif"]').data().hyena;//Edited
						if(!D.sts)return;
						Hyena.ST($P,D,false,false);
					});


					///////////////////
					// SCROLL INTO VIEW
					///////////////////
				}else if(D.on_scroll){
					Hyena.SCR($P,D);
					$(window).on('load',function(){ $(document).scroll(); });

					/////////////////////////////
					// NO CONTROLS - CLICK EVENTS
					/////////////////////////////
				}
				//else{  //Edited
					// ADD EVENT TO PLAY GIF ON CLICK
					$P.css('cursor','pointer').on($S.cEv,function(e){
						if(!e.handled){
							var $P=$(this),
								D=$P.find('img[src*=".gif"]').data().hyena;//Edited

							if(D.tmrOn>0) return;

							if(!D.sts){ Hyena.PL($P,D);
							}else{ 		Hyena.ST($P,D,false,false); };
						};
					});

			//	}; //Edited

				/////////////////////////////////
				// SETUP CANVAS AND PREP CONTROLS
				/////////////////////////////////
				Hyena.ST($P,D,D.show_button,true);
			};


			///////////////////////////////////////////////////////
			// UPDATE MEDIA PLAYER BUTTON POSITION ON WINDOW RESIZE
			///////////////////////////////////////////////////////
			$(window).on('resize',function(){
				var $players=$('div.hy_plyr'),
					numPlayers=$players.length;
				for(var i=0; i<numPlayers; i++){
					var $B=$($players[i]).find('img.hy_btn');
					$B.css({'margin-top':-($B.outerHeight())/2+'px','margin-left':-($B.outerWidth())/2+'px'});
				};
			}).resize();;
		},

////////////////////
// PLAY ANIMATED GIF
////////////////////
		PL:function($P,D){
			if(D.sts) return;

			// SETUP VARIABLES
			var $G=$P.find('img[src*=".gif"]'),//Edited
				$C=$P.find('canvas'),
				$S=$P.find('div.hyena_slate'),
				$B=$P.find('img.hy_btn');

			///////////////////////////
			// HIDE CANVAS AND SHOW GIF
			///////////////////////////
			$C[0].style.display='none';
			$G.css({visibility:'visible', display:'block'});

			//////////////////////////
			// ANIMATE SLATE LAYER OUT
			//////////////////////////
			$S.stop(true,false).animate({'opacity':0},{duration:D.slate.S(0).P(),queue:false});
			if($.support.Hyena.isTablet) $B.css('opacity',0);

			/////////////////////////
			// STOP ON OPTIONAL TIMER
			/////////////////////////
			if(D.tmr_Off>0) D.STMR=setTimeout(function(){
				clearTimeout(D.TMR);
				Hyena.ST($P,D,false,false);
			},D.tmr_Off);

			// UPDATE STATUS
			D.sts=true;
			$P.addClass('play');//Edited (added)
		},


/////////////////////
// PAUSE ANIMATED GIF
/////////////////////
		ST:function($P,D,SHW,init){
			var $G=$P.find('img[src*=".gif"]'),//Edited
				I=new Image(),
				Button=new Image();
			// ADD THE CANVAS
			if(!$P.find('canvas').length) $('<canvas class="hyena_canvas"/>').insertBefore($G);

			/////////////////////
			// LOAD GIF TO CANVAS
			/////////////////////
			I.onload=function(){
				var $C=$P.find('canvas')[0],
					CTX=$C.getContext('2d'),
					W=this.width,
					H=this.height,
					$S=$P.find('div.hyena_slate');

				//////////////////////////////////////////////////////////////////////////
				// DRAW TO THE CANVAS - RESPONSIVENESS CONFORMS TO WIDTH OF PARENT ELEMENT
				//////////////////////////////////////////////////////////////////////////
				$C.width=W;
				$C.height=H;
				$C.style.display='block';
				CTX.drawImage(I,0,0,W,H);

				///////////////////
				// SHOW SLATE LAYER
				///////////////////
				$S.css('opacity',0.01).animate({'opacity':D.slate.S(1).P()},{duration:D.slate.S(0).P(),queue:false});

				//////////////////////////////////
				// IF PLAYER IS HIDDEN, FADE IT IN
				//////////////////////////////////
				if($P.css('visibility')==='hidden') $P.css({visibility:'visible',opacity:0}).fadeTo(D.player_fade_speed.P(),1);

				// HIDE GIF
				$G.css({visibility:'hidden',display:'none'});

				// PLAY ON OPTIONAL TIMER
				if(D.tmrOn>0) D.TMR=setTimeout(function(){
					clearTimeout(D.STMR);
					Hyena.PL($P,D);
				},D.tmrOn);
			};
			I.src=$G.attr('src');


			/////////////////////////
			// INITIAL CONTROLS SETUP
			/////////////////////////
			if(D.controls){
				var BS=init || D.sts ? 'play' : 'stop';
				Button.onload=function(){
					var $B=$P.find('img.hy_btn');

					// SHOW_BUTTON SETTING
					if(!SHW && !$.support.Hyena.isTablet && !$.support.Hyena.isMobile){
						$B[0].style.display='none';
					}else{
						// ASSIGN PLAY BUTTON GRAPHIC AND FADE IN
						$B.attr('src','Hyena/controls/'+D.style+'_'+BS+'.png')
							.css({'margin-top':-($B.outerHeight())/2+'px','margin-left':-($B.outerWidth())/2+'px'})
							.stop(true,false).animate({opacity:D.control_opacity.S(1).P()},{duration:D.fade_speed.S(0).P(),queue:false});
					};
				};
				Button.src='Hyena/controls/'+D.style+'_'+BS+'.png';
			};

			// UPDATE STATUS
			D.sts=false;
			$P.removeClass('play'); //Edited (added)
		},


//////////////////
// SCROLLING EVENT
//////////////////
		SCR:function(o,D){
			// SETUP EVENT FOR ON_SCROLL
			$(document).on('scroll',function(){

				var w=$(window),
					dT=w.scrollTop(),
					dB=dT+w.height(),
					eT=o.offset().top,
					eB=eT+o.height(),
					customOffset=w.height()*D.scroll_offset.P(),
					inView=(eT <= dT+w.height()/2) && (eB >= dT + o.height()/4); //Edited


				/* IF IN VIEW DISPLAY ANIMATION */
				if(inView){
					if(D.sts)return;
					Hyena.PL(o,D);
				}else{
					if(!D.sts)return;
					Hyena.ST(o,D,false,false);
				};
			});
		}};

	$.fn.Hyena=function(method,options){
		if(Hyena[method]){ return Hyena[method].apply(this,Array.prototype.slice.call(arguments,1));
		}else if(typeof method==='object'||!method){ return Hyena.init.apply(this,arguments);
		}else{ $.error('Method '+method+' does not exist'); }
	}})(jQuery);
/* EXTEND JQUERY SUPPORT FUNCTIONS */(function(){var uA=navigator.userAgent.toLowerCase(); jQuery.support.Hyena={'cEv':!!('ontouchstart' in window)?'touchstart':'click','isTablet':uA.match(/iPad|Android|Kindle|NOOK|tablet/i)!==null,'isMobile':(/android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i.test(navigator.userAgent.toLowerCase()))}})();