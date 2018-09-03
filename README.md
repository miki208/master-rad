# Развој микросервисне апликације за Android коришћењем окружења Lumen

### Значај теме и области

Архитектура заснована на микросервисима представља модеран начин организациjе софтвера као колекциjе лабаво повезаних сервиса коjи имплементираjу жељену пословну логику, а коjи међусобно комуницираjу jедноставним механизмима комуникациjе, тј. путем веома једноставних интерфејса за програмирање апликација (енг. *Application Programming Interface*, скраћено API). Микросервиси омогућаваjу брзо локализовање грешака, добро скалираjу и jедноставно се интегришу са другим сервисима. Jедан од наjчешћих приступа дизаjну микросервиса jе коришћењем REST архитектуре и у том случају одговарајући API коjим се микросервиси излажу спољашњем свету назива се RESTful API. Развоjни оквир Lumen, настао 2015. године, омогућава развоj ефикасних микросервиса и RESTful API-ja. 

OAuth 2 је протокол за ауторизацију који омогућава развој апликација које могу да остваре ограничен приступ корисничким налозима у оквиру постојећих мрежа (нпр. мрежа Facebook или GitHub) користећи HTTP сервис. Овај протокол омогућава делегирање аутентикације сервису који садржи кориснички налог, и омогућава ауторизацију апликацији тако да може да приступи подацима корисничког налога. OAuth 2 се користи за развој апликација за десктоп рачунаре и мобилне уређаје, као и за развој веб апликација.

### Специфични циљ рада

Коришћењем развојног оквира Lumen, развити RESTful API са OAuth2 ауторизацијом, и применити га на развој Android апликације за проналажење друштва за трчање. Апликација треба да искористи интерфејсе за приступ спољашњим сервисима који ће омогућити повезивање корисничког налога са неким од налога на постојећим спортским апликацијама за трчање, у циљу преузимања података који ће бити коришћени приликом проналажења друштва на основу различитих критеријума. Анализирати предности и мане овакве архитектуре, као и могућа унапређења.

### Литература

1. Charles Bihis. *Mastering OAuth 2.0*, Packt Publishing Ltd (2015)</br>
2. Leonard Richardson et al. *RESTful Web APIs*, O’Reilly Media, Inc (2013)</br>
3. Sam Newman, *Building Microservices: Designing Fine-Grained Systems*, O'Reilly Media, Inc (2015)
4. David Griffiths, *Head First Android Development: A Brain-Friendly Guide*, O'Reilly Media, Inc (2017)
5. *Lumen documentation https://lumen.laravel.com/docs/5.6*
6. *Documentation for app developers https://developer.android.com/docs/*
