from pathlib import Path
from PIL import Image,ImageDraw,ImageFont,ImageFilter
import shutil
P=Path(__file__).resolve().parents[1]
M=P/'marketplace';M.mkdir(exist_ok=True)
source=Path('/mnt/data/wide_elegant_luxurious_marketing_poster_style_gr.png')
if source.exists():shutil.copyfile(source,M/'illustrative-concept-cover.png')
F='/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf'
FB='/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'
FS='/usr/share/fonts/truetype/dejavu/DejaVuSerif.ttf'
def font(n,b=False,serif=False):return ImageFont.truetype(FS if serif else FB if b else F,n)
ink='#0e1519'; ivory='#f3eee5'; gold='#c9a56a'; muted='#acafa9'
icon=Image.new('RGB',(80,80),ink); d=ImageDraw.Draw(icon)
d.rounded_rectangle((2,2,77,77),radius=19,outline=gold,width=2)
d.ellipse((22,17,54,49),outline=gold,width=4)
d.line((50,45,64,60),fill=gold,width=5)
icon.save(M/'item-thumbnail-80x80.png')
def base(title,subtitle,tag):
 im=Image.new('RGB',(1170,780),ivory);d=ImageDraw.Draw(im)
 d.rounded_rectangle((40,40,1130,740),radius=26,fill=ink)
 d.text((77,70),'✦  LUXEFIND / SEARCH PRO',font=font(18,True),fill=gold)
 d.text((77,115),title,font=font(51,serif=True),fill='#FFFFFF')
 d.text((80,184),subtitle,font=font(17),fill='#BAB9B8')
 d.text((80,707),tag.upper(),font=font(13,True),fill=gold)
 return im,d
# Preview 1: Embed actual mock UI screenshot (not upscale)
im,d=base('One search. Many sources.','Premium metasearch interface for WordPress','Simulation / design preview — actual plugin UI')
s=Image.open(P/'tests/ui-desktop.png').convert('RGB')
s=s.crop((140,88,1200,690));s.thumbnail((1015,480),Image.Resampling.LANCZOS)
d.rounded_rectangle((72,221,1099,680),radius=14,fill='#282d30',outline='#977f55',width=2)
im.paste(s,(82+(1005-s.width)//2,237))
im.save(M/'preview-desktop-1170x780.png',optimize=True)
# Preview 2: Smartphone UI screenshot + factual feature callouts
im,d=base('Designed for every screen.','Refined touch targets, keyboard navigation and reduced-motion support','Mock results / responsive browser screenshot')
s=Image.open(P/'tests/ui-mobile.png').convert('RGB')
s.thumbnail((285,468),Image.Resampling.LANCZOS)
im.paste(s,(104,225))
d.rounded_rectangle((90,213,105+s.width+8,226+s.height+5),radius=12,outline=gold,width=3)
features=['Responsive search panel','Native Gutenberg block','Local site search','Optional external providers','Safe text rendering']
for i,entry in enumerate(features):
 y=260+i*75
 d.ellipse((513,y+8,537,y+32),fill=gold)
 d.text((515,y+5),'✓',font=font(20,True),fill=ink)
 d.text((557,y+6),entry,font=font(23),fill='#F1EFEA')
im.save(M/'preview-mobile-1170x780.png',optimize=True)
# Preview 3: Clearly differentiate source and test boundaries
im,d=base('Built for modern publishing.','Site content remains local. External web discovery is optional.','Diagram / no live API verification implied')
blocks=[('WordPress','Published content'),('LuxeFind','REST + ranking'),('Providers','Optional paid APIs')]
for i,(h,sub) in enumerate(blocks):
 x=83+i*355
 d.rounded_rectangle((x,295,x+288,502),radius=23,fill='#202d33',outline='#777058',width=2)
 d.ellipse((x+119,323,x+164,368),fill=gold)
 d.text((x+28,386),h,font=font(29,serif=True),fill='white')
 d.text((x+28,448),sub,font=font(17),fill=muted)
 if i<2:d.text((x+307,386),'→',font=font(37,True),fill=gold)
d.text((95,561),'Brave  •  Mojeek  •  Wikipedia  •  Nominatim  •  LibreTranslate',font=font(18),fill='#DEDEDC')
d.text((95,608),'Third-party accounts and permitted quotas are supplied by the buyer.',font=font(15),fill=muted)
im.save(M/'preview-architecture-1170x780.png',optimize=True)
print('Assets',[(x.name,Image.open(x).size) for x in M.glob('*.png')])
